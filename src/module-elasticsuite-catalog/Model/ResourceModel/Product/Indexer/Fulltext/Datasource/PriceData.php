<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;

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
     * @var Attribute
     */
    private $eavAttribute;

    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Attribute $eavAttribute
    )
    {
        $this->eavAttribute = $eavAttribute;

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

        $taxClassAttrId = $this->eavAttribute->getIdByCode('catalog_product', 'tax_class_id');

        $select = $this->getConnection()->select()
            ->from(['p' => $this->getTable('catalog_product_index_price')])
            ->join(
                ['tcd' => $this->getTable('catalog_product_entity_int')],
                $this->taxClassJoinCondition('tcd', $taxClassAttrId, Store::DEFAULT_STORE_ID),
                []
            )
            ->joinLeft(
                ['tcs' => $this->getTable('catalog_product_entity_int')],
                $this->taxClassJoinCondition('tcs', $taxClassAttrId, $storeId),
                []
            )
            ->columns([
                'tax_class_id' => 'IFNULL(tcs.value, tcd.value)'
            ])
            ->where('p.website_id = ?', $websiteId)
            ->where('p.entity_id IN(?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }

    private function taxClassJoinCondition($alias, $taxClassAttrId, $storeId)
    {
        return join(' AND ', [
                $alias . '.row_id = p.entity_id',
                $this->getConnection()->quoteInto($alias . '.store_id = ?', $storeId),
                $this->getConnection()->quoteInto($alias . '.attribute_id = ?', $taxClassAttrId)
            ]
        );
    }
}
