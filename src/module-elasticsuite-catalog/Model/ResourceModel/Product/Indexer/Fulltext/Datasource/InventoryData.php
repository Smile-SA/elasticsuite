<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Eav\Indexer\AbstractIndexer;

/**
 * Catalog Inventory Data source resource model
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InventoryData extends AbstractIndexer
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var int[]
     */
    private $stockIdByWebsite = [];

    /**
     * InventoryData constructor.
     *
     * @param ResourceConnection     $resource      Database adapter.
     * @param StoreManagerInterface  $storeManager  Store manager.
     * @param StockRegistryInterface $stockRegistry Stock registry.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($resource, $storeManager);
    }

    /**
     * Load inventory data for a list of product ids and a given store.
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

        $select = $this->getConnection()->select()
            ->from(['ciss' => $this->getTable('cataloginventory_stock_status')], ['product_id', 'stock_status', 'qty'])
            ->where('ciss.stock_id = ?', $stockId)
            ->where('ciss.website_id = ?', $websiteId)
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
    private function getStockId($websiteId)
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
    private function getWebsiteId($storeId)
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }
}
