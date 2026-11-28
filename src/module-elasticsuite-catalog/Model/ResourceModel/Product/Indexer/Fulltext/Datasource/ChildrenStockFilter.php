<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Legacy CatalogInventory implementation of the children stock filter.
 *
 * Used when MSI modules are not installed. Salability is read from the legacy stock status index,
 * which is the same source of truth as the legacy inventory datasource.
 *
 * @see ChildrenStockFilterMSI
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class ChildrenStockFilter extends AbstractChildrenStockFilter
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * Constructor.
     *
     * @param ResourceConnection          $resource           Database adapter.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param MetadataPool                $metadataPool       Metadata pool.
     * @param StockConfigurationInterface $stockConfiguration Stock configuration.
     * @param StockRegistryInterface      $stockRegistry      Stock registry.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        StockConfigurationInterface $stockConfiguration,
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;

        parent::__construct($resource, $storeManager, $metadataPool, $stockConfiguration);
    }

    /**
     * {@inheritdoc}
     */
    protected function applySalableFilter(Select $select, string $childAlias, int $storeId): Select
    {
        $connection  = $this->getConnection();
        $idFieldName = $this->getEntityMetaData(ProductInterface::class)->getIdentifierField();

        $joinCondition = sprintf(
            '%s = %s AND %s = %d AND %s = %d AND %s = 1',
            $connection->quoteIdentifier($childAlias . '.' . $idFieldName),
            $connection->quoteIdentifier('stock_index.product_id'),
            $connection->quoteIdentifier('stock_index.stock_id'),
            $this->getStockId($storeId),
            $connection->quoteIdentifier('stock_index.website_id'),
            (int) $this->getStockConfiguration()->getDefaultScopeId(),
            $connection->quoteIdentifier('stock_index.stock_status')
        );

        return $select->joinInner(
            ['stock_index' => $this->getTable('cataloginventory_stock_status')],
            new \Zend_Db_Expr($joinCondition),
            []
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveStockId(int $websiteId): int
    {
        return (int) $this->stockRegistry->getStock($websiteId)->getStockId();
    }
}
