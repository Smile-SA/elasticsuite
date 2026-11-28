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

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Multi Source Inventory implementation of the children stock filter.
 *
 * Children salability is read from the stock index of the stock assigned to the website of the
 * store being indexed. For the default stock, that index is a view built on the legacy tables.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class ChildrenStockFilterMSI extends AbstractChildrenStockFilter
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\InventorySalesApi\Api\StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface
     */
    private $stockIndexTableProvider;

    /**
     * Constructor.
     *
     * @param ResourceConnection          $resource           Database adapter.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param MetadataPool                $metadataPool       Metadata pool.
     * @param StockConfigurationInterface $stockConfiguration Stock configuration.
     * @param ObjectManagerInterface      $objectManager      Object manager.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        StockConfigurationInterface $stockConfiguration,
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;

        parent::__construct($resource, $storeManager, $metadataPool, $stockConfiguration);
    }

    /**
     * {@inheritdoc}
     */
    protected function applySalableFilter(Select $select, string $childAlias, int $storeId): Select
    {
        $tableName  = $this->getStockIndexTableProvider()->execute($this->getStockId($storeId));
        $connection = $this->getConnection();

        $joinCondition = sprintf(
            '%s = %s AND %s = 1',
            $connection->quoteIdentifier($childAlias . '.sku'),
            $connection->quoteIdentifier('stock_index.' . \Magento\InventoryIndexer\Indexer\IndexStructure::SKU),
            $connection->quoteIdentifier('stock_index.' . \Magento\InventoryIndexer\Indexer\IndexStructure::IS_SALABLE)
        );

        return $select->joinInner(['stock_index' => $tableName], new \Zend_Db_Expr($joinCondition), []);
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveStockId(int $websiteId): int
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

        $stock = $this->getStockResolver()->execute(
            \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
            $websiteCode
        );

        return (int) $stock->getStockId();
    }

    /**
     * Fetch the Stock Resolver from Object Manager instead of constructor to avoid compilation error
     * when MSI modules are not there.
     * The fact that the class exists is normally already checked in the caller class.
     * @see ChildrenStockFilterResolver
     *
     * @return \Magento\InventorySalesApi\Api\StockResolverInterface|null
     */
    private function getStockResolver()
    {
        if (null === $this->stockResolver) {
            $this->stockResolver = $this->objectManager->get(
                \Magento\InventorySalesApi\Api\StockResolverInterface::class
            );
        }

        return $this->stockResolver;
    }

    /**
     * Fetch the Stock Index Table Provider from Object Manager instead of constructor
     * to avoid compilation error when MSI modules are not there.
     *
     * @return \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface|null
     */
    private function getStockIndexTableProvider()
    {
        if (null === $this->stockIndexTableProvider) {
            $this->stockIndexTableProvider = $this->objectManager->get(
                \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface::class
            );
        }

        return $this->stockIndexTableProvider;
    }
}
