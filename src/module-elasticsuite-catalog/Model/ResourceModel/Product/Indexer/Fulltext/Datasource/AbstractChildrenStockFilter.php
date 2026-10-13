<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

/**
 * Common implementation of the children stock filters.
 *
 * Takes care of the "Display Out of Stock Products" configuration and of the stock id caching,
 * the actual stock table join being left to the implementations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
abstract class AbstractChildrenStockFilter extends Indexer implements ChildrenStockFilterInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var int[]
     */
    private $stockIdByWebsite = [];

    /**
     * Constructor.
     *
     * @param ResourceConnection          $resource           Database adapter.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param MetadataPool                $metadataPool       Metadata pool.
     * @param StockConfigurationInterface $stockConfiguration Stock configuration.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockConfiguration = $stockConfiguration;

        parent::__construct($resource, $storeManager, $metadataPool);
    }

    /**
     * {@inheritdoc}
     */
    public function addSalableFilter(Select $select, string $childAlias, int $storeId): Select
    {
        if ($this->stockConfiguration->isShowOutOfStock($storeId)) {
            return $select;
        }

        return $this->applySalableFilter($select, $childAlias, $storeId);
    }

    /**
     * Restrict the select to the children being salable for the given store.
     *
     * @param Select $select     Children select.
     * @param string $childAlias Alias of the child product entity table in the select.
     * @param int    $storeId    Store id.
     *
     * @return Select
     */
    abstract protected function applySalableFilter(Select $select, string $childAlias, int $storeId): Select;

    /**
     * Retrieve the stock id associated with a website.
     *
     * @param int $websiteId Website id.
     *
     * @return int
     */
    abstract protected function resolveStockId(int $websiteId): int;

    /**
     * Retrieve the stock id to use for a given store, resolved only once per website.
     *
     * @param int $storeId Store id.
     *
     * @return int
     */
    protected function getStockId(int $storeId): int
    {
        $websiteId = (int) $this->getStore($storeId)->getWebsiteId();

        if (!isset($this->stockIdByWebsite[$websiteId])) {
            $this->stockIdByWebsite[$websiteId] = $this->resolveStockId($websiteId);
        }

        return $this->stockIdByWebsite[$websiteId];
    }

    /**
     * Stock configuration.
     *
     * @return StockConfigurationInterface
     */
    protected function getStockConfiguration(): StockConfigurationInterface
    {
        return $this->stockConfiguration;
    }
}
