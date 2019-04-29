<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;

/**
 * Prices data datasource resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceData extends Indexer
{
    /**
     * @var DimensionCollectionFactory
     */
    private $dimensionCollectionFactory;

    /**
     * @var array
     */
    private $dimensions;

    /**
     * @var array
     */
    private $dimensionsByWebsite;

    /**
     * @var PriceTableResolver
     */
    private $priceTableResolver;

    /**
     * PriceData constructor.
     *
     * @param ResourceConnection         $resource                   Database adapter.
     * @param StoreManagerInterface      $storeManager               Store Manager.
     * @param MetadataPool               $metadataPool               Metadata Pool.
     * @param DimensionCollectionFactory $dimensionCollectionFactory Dimension collection factory.
     * @param PriceTableResolver         $priceTableResolver         Price index table resolver.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        DimensionCollectionFactory $dimensionCollectionFactory,
        PriceTableResolver $priceTableResolver
    ) {
        $this->dimensionCollectionFactory = $dimensionCollectionFactory;
        $this->dimensions = null;
        $this->dimensionsByWebsite = [];
        $this->priceTableResolver = $priceTableResolver;
        parent::__construct($resource, $storeManager, $metadataPool);
    }

    /**
     * Load prices data for a list of product ids and a given store.
     *
     * @param integer $storeId    Store id.
     * @param array   $productIds Product ids list.
     *
     * @return array
     */
    public function loadPriceData($storeId, $productIds)
    {
        $websiteId = $this->getStore($storeId)->getWebsiteId();

        $baseSelects = [];
        foreach ($this->getPriceIndexDimensionsTables($websiteId) as $dimensionsTable) {
            $baseSelects[] = $this->getLoadPriceDataSelect($dimensionsTable, $websiteId, $productIds);
        }
        $select = $this->getConnection()->select()->union($baseSelects);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Return a single price loading query against a specific price index table.
     *
     * @param string  $indexTable Price index table.
     * @param integer $websiteId  Website id.
     * @param array   $productIds Product ids list.
     *
     * @return \Magento\Framework\DB\Select
     */
    private function getLoadPriceDataSelect($indexTable, $websiteId, $productIds)
    {
        $select = $this->getConnection()->select()
            ->from(['p' => $indexTable])
            ->where('p.website_id = ?', $websiteId)
            ->where('p.entity_id IN(?)', $productIds);

        return $select;
    }

    /**
     * Return the price index tables according to the price index dimensions for the given website.
     *
     * @param integer $websiteId Website id.
     *
     * @return array
     */
    private function getPriceIndexDimensionsTables($websiteId)
    {
        $tables = [];

        $indexDimensions = $this->getPriceIndexDimensions($websiteId);
        foreach ($indexDimensions as $dimensions) {
            $tables[] = $this->priceTableResolver->resolve('catalog_product_index_price', $dimensions);
        }

        return $tables;
    }

    /**
     * Return price index dimensions applicable for the given website.
     *
     * @param integer $websiteId Website id.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getPriceIndexDimensions($websiteId)
    {
        if (!array_key_exists($websiteId, $this->dimensionsByWebsite)) {
            $indexDimensions = $this->getAllPriceIndexDimensions();

            $relevantDimensions = [];
            foreach ($indexDimensions as $dimensions) {
                if (array_key_exists(WebsiteDimensionProvider::DIMENSION_NAME, $dimensions)) {
                    $websiteDimension = $dimensions[WebsiteDimensionProvider::DIMENSION_NAME];
                    if ((string) $websiteDimension->getValue() == $websiteId) {
                        $relevantDimensions[] = $dimensions;
                    }
                } else {
                    $relevantDimensions[] = $dimensions;
                }
            }

            $this->dimensionsByWebsite[$websiteId] = $relevantDimensions;
        }

        return $this->dimensionsByWebsite[$websiteId];
    }

    /**
     * Return all price index dimensions.
     *
     * @return array
     */
    private function getAllPriceIndexDimensions()
    {
        if ($this->dimensions === null) {
            $this->dimensions = $this->dimensionCollectionFactory->create();
        }

        return $this->dimensions;
    }
}
