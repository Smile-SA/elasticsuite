<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryProduct as CategoryProductResource;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service class responsible for migrating product positions from Magento legacy catalog table to ElasticSuite table.
 *
 * This logic is part of the CLI command Smile\ElasticsuiteCatalog\Console\Command\CategoryPositionMigrate.
 *
 * Responsibilities:
 * - Fetch legacy positions from `catalog_category_product`.
 * - Normalize negative/zero/positive positions according to rules.
 * - Resolve conflicts (reorder or delete duplicates).
 * - Persist normalized positions into ElasticSuite table
 *   `smile_virtualcategory_catalog_category_product_position` (store_id = 0).
 * - Provide helper methods for CLI previews (dry-run).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CategoryPositionMigrator
{
    /**
     * @var string
     */
    const TARGET_TABLE_NAME = 'smile_virtualcategory_catalog_category_product_position';

    /**
     * @var string Product visibility table (int values).
     */
    const PRODUCT_VISIBILITY_TABLE_NAME = 'catalog_product_entity_int';

    /**
     * @var int Number of rows displayed in CLI preview output.
     */
    const PREVIEW_LIMIT = 100;

    /**
     * @var int Number of records to insert in a single batch during migration.
     */
    const BATCH_SIZE = 1000;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var CategoryProductResource
     */
    private $categoryProductResource;

    /**
     * Eav config.
     *
     * @var Config
     */
    private $eavConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ResourceConnection        $resource                  Resource connection for DB access.
     * @param CategoryCollectionFactory $categoryCollectionFactory Magento category collection factory.
     * @param CategoryProductResource   $categoryProductResource   Magento legacy category-product relation resource model.
     * @param Config                    $eavConfig                 Eav config.
     * @param LoggerInterface           $logger                    PSR-compliant logger for debug and error logging.
     */
    public function __construct(
        ResourceConnection $resource,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryProductResource $categoryProductResource,
        Config $eavConfig,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryProductResource = $categoryProductResource;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
    }

    /**
     * Get all category IDs (excluding root categories).
     *
     * @return int[]
     */
    public function getAllCategoryIds(): array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('level', ['gt' => 1]) // Exclude root.
            ->setOrder('entity_id', 'ASC');

        return $collection->getAllIds();
    }

    /**
     * Run migration for all categories in the catalog.
     *
     * @param array<string,mixed> $options Migration options (migrateNegative, migrateZero, migratePositive, conflictStrategy).
     * @param bool                $dryRun  Whether to simulate changes without writing to DB.
     * @param OutputInterface     $output  CLI output for progress display.
     *
     * @return array{migrated:int,deleted:int,normalized:array<int,int>,preview:array<int,array<int,int>>}
     */
    public function wholeCatalogMigration(array $options, bool $dryRun, OutputInterface $output): array
    {
        $categoryIds = $this->getAllCategoryIds();
        $output->writeln(sprintf('<info>Processing %d categories...</info>', count($categoryIds)));

        $totalMigrated = 0;
        $previewRows = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $result = $this->migrateCategoryPositions(
                    $categoryId,
                    $dryRun,
                    $options['migrateNegative'],
                    $options['migrateZero'],
                    $options['migratePositive'],
                    $options['conflictStrategy']
                );

                $totalMigrated += $result['migrated'];

                // Only gather limited preview rows.
                if ($dryRun && count($previewRows) < self::PREVIEW_LIMIT) {
                    foreach ($result['preview'] as $row) {
                        if (count($previewRows) >= self::PREVIEW_LIMIT) {
                            break;
                        }
                        $previewRows[] = $row;
                    }
                }
            } catch (LocalizedException $e) {
                $this->logger->error(sprintf('Migration failed for category %d: %s', $categoryId, $e->getMessage()));
                $output->writeln(sprintf('<error>Category %d failed: %s</error>', $categoryId, $e->getMessage()));
            }
        }

        return [
            'categories'      => count($categoryIds),
            'migrated'        => $totalMigrated,
            'preview_headers' => ['Category ID', 'Product ID', 'Old Position', 'New Position'],
            'preview'         => $previewRows,
        ];
    }

    /**
     * Run migration for a single category.
     *
     * @param int                 $categoryId Category ID.
     * @param array<string,mixed> $options    Migration options (migrateNegative, migrateZero, migratePositive, conflictStrategy).
     * @param bool                $dryRun     Whether to simulate without persisting data.
     * @param OutputInterface     $output     CLI output for messaging.
     *
     * @return array{migrated:int,deleted:int,normalized:array<int,int>,preview:array<int,array<int,int>>}
     */
    public function singleCategoryMigration(int $categoryId, array $options, bool $dryRun, OutputInterface $output): array
    {
        $output->writeln(sprintf('<info>Processing category ID: %d</info>', $categoryId));

        $result = $this->migrateCategoryPositions(
            $categoryId,
            $dryRun,
            $options['migrateNegative'],
            $options['migrateZero'],
            $options['migratePositive'],
            $options['conflictStrategy']
        );

        $previewRows = $result['preview'];

        return [
            'categories'       => 1,
            'migrated'         => $result['migrated'],
            'preview_headers'  => ['Category ID', 'Product ID', 'Old Position', 'New Position'],
            'preview'          => array_slice($previewRows, 0, 100),
        ];
    }

    /**
     * Core logic: Migrate or preview product positions for one category.
     *
     *  This method:
     *  - Uses pagination to avoid memory issues.
     *  - Filters out non-visible products (e.g. children of configurables).
     *  - Normalizes positions to positive contiguous values.
     *  - Handles conflicts (delete or reorder).
     *  - Collects first 100 rows with old and new positions.
     *  - Inserts data in batches for performance.
     *
     * @param int    $categoryId       Category to process.
     * @param bool   $dryRun           If true, no database writes are performed.
     * @param bool   $migrateNegative  Include negative positions.
     * @param bool   $migrateZero      Include zero positions.
     * @param bool   $migratePositive  Include positive positions.
     * @param string $conflictStrategy Conflict resolution mode ('reorder' or 'delete').
     *
     * @return array{migrated:int,deleted:int,normalized:array<int,int>,preview:array<int,array<int,mixed>>}
     *
     * @throws LocalizedException If any database or transactional error occurs.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function migrateCategoryPositions(
        int $categoryId,
        bool $dryRun,
        bool $migrateNegative,
        bool $migrateZero,
        bool $migratePositive,
        string $conflictStrategy
    ): array {
        $conflictStrategy = ($conflictStrategy === 'delete') ? 'delete' : 'reorder';

        $legacyTable     = $this->categoryProductResource->getMainTable();
        $targetTable     = $this->resource->getTableName(self::TARGET_TABLE_NAME);
        $visibilityTable = $this->resource->getTableName(self::PRODUCT_VISIBILITY_TABLE_NAME);

        // Resolve visibility attribute ID.
        $visibilityAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'visibility');
        $visibilityAttributeId = (int) $visibilityAttribute->getAttributeId();

        $storeId = 0;
        $offset = 0;

        $processedOriginalPositions = [];
        $toDelete = [];
        $normalized = [];
        $previewRows = [];

        $nextPos = 1;

        do {
            // Fetch a batch of products with left join to visibility.
            $select = $this->connection->select()
                ->from(['ccp' => $legacyTable], ['product_id', 'position'])
                ->joinLeft(
                    ['cpei' => $visibilityTable],
                    'cpei.row_id = ccp.product_id'
                    . ' AND cpei.attribute_id = ' . $visibilityAttributeId
                    . ' AND cpei.store_id = ' . $storeId,
                    []
                )
                ->where('ccp.category_id = ?', $categoryId)
                // Treat NULL visibility as VISIBILITY_BOTH.
                ->order('ccp.position ASC')
                ->limit(self::BATCH_SIZE, $offset);

            $rows = $this->connection->fetchAll($select);
            $rowCount = count($rows);

            if ($rowCount === 0) {
                break;
            }

            foreach ($rows as $row) {
                $productId   = (int) $row['product_id'];
                $originalPos = (int) $row['position'];
                $visibility  = $row['value'] ?? Visibility::VISIBILITY_BOTH;

                // Respect visibility: skip "Not Visible Individually".
                if ($visibility == Visibility::VISIBILITY_NOT_VISIBLE) {
                    continue;
                }

                // Apply migration filters (negative, zero, positive).
                if (($originalPos < 0 && !$migrateNegative) ||
                    ($originalPos === 0 && !$migrateZero) ||
                    ($originalPos > 0 && !$migratePositive)
                ) {
                    continue;
                }

                // Conflict handling: delete or reorder.
                if ($conflictStrategy === 'delete') {
                    if (isset($processedOriginalPositions[$originalPos])) {
                        $toDelete[] = $productId;
                        continue;
                    }
                }

                // Mark original position as processed.
                $processedOriginalPositions[$originalPos] = true;

                // Assign new sequential position.
                $normalized[$productId] = $nextPos;

                // Collect preview rows.
                if (count($previewRows) < self::PREVIEW_LIMIT) {
                    $previewRows[] = [
                        $categoryId,
                        $productId,
                        $originalPos,
                        $nextPos,
                    ];
                }

                $nextPos++;
            }

            $offset += self::BATCH_SIZE;
        } while ($rowCount === self::BATCH_SIZE);

        // If dry-run or nothing to persist, return immediately.
        if ($dryRun || empty($normalized)) {
            return [
                'migrated'   => count($normalized),
                'deleted'    => count($toDelete),
                'normalized' => $normalized,
                'preview'    => $previewRows,
            ];
        }

        // Persist in batches.
        $this->connection->beginTransaction();
        try {
            // Clear existing positions for this category/store.
            $this->connection->delete($targetTable, ['category_id = ?' => $categoryId, 'store_id = ?' => $storeId]);

            $batch = [];
            foreach ($normalized as $productId => $position) {
                $batch[] = [
                    'category_id' => $categoryId,
                    'product_id'  => $productId,
                    'store_id'    => $storeId,
                    'position'    => $position,
                ];
                if (count($batch) >= self::BATCH_SIZE) {
                    $this->connection->insertMultiple($targetTable, $batch);
                    $batch = [];
                }
            }
            if (!empty($batch)) {
                $this->connection->insertMultiple($targetTable, $batch);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $this->logger->error(sprintf('Migration failed for category %d: %s', $categoryId, $e->getMessage()));
            throw new LocalizedException(__('Migration failed for category %1: %2', $categoryId, $e->getMessage()));
        }

        return [
            'migrated'   => count($normalized),
            'deleted'    => count($toDelete),
            'normalized' => $normalized,
            'preview'    => $previewRows,
        ];
    }
}
