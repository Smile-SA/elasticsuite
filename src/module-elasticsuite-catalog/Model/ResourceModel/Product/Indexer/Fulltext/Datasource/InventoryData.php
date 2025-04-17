<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\InventoryDataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Multi Source Inventory Catalog Inventory Data source resource model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData extends Indexer implements InventoryDataInterface
{
    /**
     * @var \Magento\InventorySalesApi\Api\StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface
     */
    private $stockIndexTableProvider;

    /**
     * @var int[]
     */
    private $stockIdByWebsite = [];

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * InventoryData constructor.
     *
     * @param ResourceConnection     $resource      Database adapter.
     * @param StoreManagerInterface  $storeManager  Store manager.
     * @param MetadataPool           $metadataPool  Metadata Pool
     * @param ObjectManagerInterface $objectManager Object Manager.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;

        parent::__construct($resource, $storeManager, $metadataPool);
    }

    /**
     * Load inventory data for a list of product ids and a given store.
     * Expected rows structure : ['product_id', 'stock_status', 'qty'].
     *
     * @param integer $storeId    Store id.
     * @param array   $productIds Product ids list.
     *
     * @return array
     */
    public function loadInventoryData($storeId, $productIds)
    {
        $websiteId = $this->getWebsiteId($storeId);
        $stockId   = $this->getStockId($websiteId);
        $tableName = $this->getStockIndexTableProvider()->execute($stockId);

        $select = $this->getConnection()->select()
            ->from(['product' => $this->getTable('catalog_product_entity')], [])
            ->join(
                ['stock_index' => $tableName],
                'product.sku = stock_index.' . \Magento\InventoryIndexer\Indexer\IndexStructure::SKU,
                [
                    'product_id'    => 'product.entity_id',
                    'stock_status'  => 'stock_index.' . \Magento\InventoryIndexer\Indexer\IndexStructure::IS_SALABLE,
                    'qty'           => 'stock_index.' . \Magento\InventoryIndexer\Indexer\IndexStructure::QUANTITY,
                ]
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->group('product.entity_id');

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Retrieve stock_id by website
     *
     * @param int $websiteId The website Id
     *
     * @return int
     */
    private function getStockId($websiteId)
    {
        if (!isset($this->stockIdByWebsite[$websiteId])) {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $stock = $this->getStockResolver()->execute(
                \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
                $websiteCode
            );
            $stockId = (int) $stock->getStockId();
            $this->stockIdByWebsite[$websiteId] = $stockId;
        }

        return $this->stockIdByWebsite[$websiteId];
    }

    /**
     * Retrieve Website Id by Store Id
     *
     * @param int $storeId The store id
     *
     * @return int
     */
    private function getWebsiteId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Fetch the Stock Resolver from Object Manager instead of constructor to avoid compilation error when MSI modules are not there.
     * The fact that the class exists is normally already checked in the caller class.
     * @see Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\InventoryData
     *
     * @return \Magento\InventorySalesApi\Api\StockResolverInterface|null
     *
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function getStockResolver()
    {
        if (null === $this->stockResolver) {
            try {
                $this->stockResolver = $this->objectManager->get(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
            } catch (\Exception $exception) {
                $message = 'Failed to fetch the MSI stock resolver despite the fact MSI implementation was considered as usable. ';
                throw new \Magento\Framework\Exception\RuntimeException(__($message . $exception->getMessage()));
            }
        }

        return $this->stockResolver;
    }

    /**
     * Fetch the Stock Index Table Provider from Object Manager instead of constructor
     * to avoid compilation error when MSI modules are not there.
     *
     * @return \Magento\InventorySalesApi\Api\StockResolverInterface|null
     *
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function getStockIndexTableProvider()
    {
        if (null === $this->stockIndexTableProvider) {
            try {
                $this->stockIndexTableProvider = $this->objectManager->get(
                    \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface::class
                );
            } catch (\Exception $exception) {
                $message = 'Failed to fetch the MSI stock table resolver despite the fact MSI implementation was considered as usable. ';
                throw new \Magento\Framework\Exception\RuntimeException(__($message . $exception->getMessage()));
            }
        }

        return $this->stockIndexTableProvider;
    }
}
