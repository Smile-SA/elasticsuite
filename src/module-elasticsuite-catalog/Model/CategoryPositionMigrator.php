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

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryProduct as CategoryProductResource;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ResourceConnection        $resource                  Resource connection for DB access.
     * @param CategoryCollectionFactory $categoryCollectionFactory Magento category collection factory.
     * @param CategoryProductResource   $categoryProductResource   Magento legacy category-product relation resource model.
     * @param LoggerInterface           $logger                    PSR-compliant logger for debug and error logging.
     */
    public function __construct(
        ResourceConnection $resource,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryProductResource $categoryProductResource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryProductResource = $categoryProductResource;
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
     * @return array{categories:int,migrated:int,preview_headers:array<int,string>,preview:array<int,array<int,mixed>>}
     */
    public function wholeCatalogMigration(array $options, bool $dryRun, OutputInterface $output): array
    {
        $categoryIds = $this->getAllCategoryIds();
        $output->writeln(sprintf('<info>Processing %d categories...</info>', count($categoryIds)));

        $totalMigrated = 0;
        $previewRows = [];
        $previewLimit = 100;

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
                if ($dryRun && count($previewRows) < $previewLimit) {
                    foreach ($result['updates'] as $row) {
                        if (count($previewRows) >= $previewLimit) {
                            break;
                        }
                        $previewRows[] = [
                            $categoryId,
                            $row['product_id'],
                            $row['position'],
                            $result['normalized'][$row['product_id']] ?? null,
                        ];
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
     * @return array{categories:int,migrated:int,preview_headers:array<int,string>,preview:array<int,array<int,mixed>>}
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

        $previewRows = [];
        foreach ($result['updates'] as $row) {
            $previewRows[] = [
                $categoryId,
                $row['product_id'],
                $row['position'],
                $result['normalized'][$row['product_id']] ?? null,
            ];
        }

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
     * @param int    $categoryId       Category to process.
     * @param bool   $dryRun           If true, no database writes are performed.
     * @param bool   $migrateNegative  Include negative positions.
     * @param bool   $migrateZero      Include zero positions.
     * @param bool   $migratePositive  Include positive positions.
     * @param string $conflictStrategy Conflict resolution mode ('reorder' or 'delete').
     *
     * @return array{migrated:int,deleted:int,updates:array<int,array{product_id:int,position:int}>,normalized:array<int,int>}
     *
     * @throws LocalizedException If any database or transactional error occurs.
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

        $legacyTable = $this->categoryProductResource->getMainTable();
        $targetTable = $this->resource->getTableName(self::TARGET_TABLE_NAME);

        // Fetch all legacy positions.
        $select = $this->connection->select()
            ->from($legacyTable, ['product_id', 'position'])
            ->where('category_id = ?', $categoryId)
            ->order('position ASC');

        $rows = $this->connection->fetchAll($select);

        if (empty($rows)) {
            return ['migrated' => 0, 'deleted' => 0, 'updates' => [], 'normalized' => []];
        }

        // Filter based on migration rules.
        $updates = array_values(array_filter(array_map(static function (array $r) use (
            $migrateNegative,
            $migrateZero,
            $migratePositive
        ): ?array {
            $pos = (int)$r['position'];
            if ($pos < 0 && !$migrateNegative) {
                return null;
            }
            if ($pos === 0 && !$migrateZero) {
                return null;
            }
            if ($pos > 0 && !$migratePositive) {
                return null;
            }
            return ['product_id' => (int)$r['product_id'], 'position' => $pos];
        }, $rows)));

        if (empty($updates)) {
            return ['migrated' => 0, 'deleted' => 0, 'updates' => [], 'normalized' => []];
        }

        // Sort by position.
        usort($updates, static fn($a, $b) => $a['position'] <=> $b['position']);

        // Normalize into sequential positions.
        $normalized = [];
        $seenPositions = [];
        $toDelete = [];
        $nextPos = 1;

        foreach ($updates as $u) {
            $pid = $u['product_id'];
            $pos = $u['position'];

            if (isset($seenPositions[$pos])) {
                if ($conflictStrategy === 'delete') {
                    $toDelete[] = $pid;
                    continue;
                }
                // reorder: just continue assigning unique new positions.
            }

            $seenPositions[$pos] = true;
            $normalized[$pid] = $nextPos++;
        }

        if ($conflictStrategy === 'delete' && !empty($toDelete)) {
            $updates = array_values(array_filter($updates, static function ($u) use ($toDelete) {
                return !in_array($u['product_id'], $toDelete, true);
            }));

            $normalized = [];
            $newPos = 1;
            foreach ($updates as $u) {
                $normalized[$u['product_id']] = $newPos++;
            }
        }

        // Persist data.
        if (!$dryRun) {
            $this->connection->beginTransaction();
            try {
                $this->connection->delete($targetTable, ['category_id = ?' => $categoryId, 'store_id = ?' => 0]);

                if (!empty($normalized)) {
                    $insertData = [];
                    foreach ($normalized as $pid => $pos) {
                        $insertData[] = [
                            'category_id' => $categoryId,
                            'product_id'  => $pid,
                            'store_id'    => 0,
                            'position'    => $pos,
                        ];
                    }
                    $this->connection->insertMultiple($targetTable, $insertData);
                }

                $this->connection->commit();
            } catch (\Throwable $e) {
                $this->connection->rollBack();
                $this->logger->error(
                    sprintf('Migration failed for category %d: %s', $categoryId, $e->getMessage())
                );
                throw new LocalizedException(
                    __('Migration failed for category %1: %2', $categoryId, $e->getMessage())
                );
            }
        }

        return [
            'migrated'   => count($normalized),
            'deleted'    => count($toDelete),
            'updates'    => $updates,
            'normalized' => $normalized,
        ];
    }
}
