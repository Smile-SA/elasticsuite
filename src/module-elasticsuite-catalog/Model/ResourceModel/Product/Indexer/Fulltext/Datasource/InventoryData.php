<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use \Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

/**
 * Catalog Inventory Data source resource model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData extends Indexer implements InventoryDataInterface
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;


    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var int[]
     */
    protected $stockIdByWebsite = [];

    /**
     * InventoryData constructor.
     *
     * @param ResourceConnection          $resource           Database adapter.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param MetadataPool                $metadataPool       Metadata Pool
     * @param StockRegistryInterface      $stockRegistry      Stock registry.
     * @param StockConfigurationInterface $stockConfiguration Stock configuration.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockRegistry      = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;

        parent::__construct($resource, $storeManager, $metadataPool);
    }

    /**
     * {@inheritdoc}
     */
    public function loadInventoryData($storeId, $productIds)
    {
        $websiteId = $this->getWebsiteId($storeId);
        $stockId   = $this->getStockId($websiteId);

        $select = $this->getConnection()->select()
            ->from(['ciss' => $this->getTable('cataloginventory_stock_status')], ['product_id', 'stock_status', 'qty'])
            ->where('ciss.stock_id = ?', $stockId)
            ->where('ciss.website_id = ?', $this->stockConfiguration->getDefaultScopeId())
            ->where('ciss.product_id IN(?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Retrieve stock_id by store
     *
     * @param int $websiteId The website Id
     *
     * @return int
     */
    protected function getStockId($websiteId)
    {
        if (!isset($this->stockIdByWebsite[$websiteId])) {
            $stockId = $this->stockRegistry->getStock($websiteId)->getStockId();
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
    protected function getWebsiteId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }
}
