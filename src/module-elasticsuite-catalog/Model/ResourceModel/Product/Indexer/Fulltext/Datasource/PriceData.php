<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

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

        $select = $this->getConnection()->select()
            ->from(['p' => $this->getTable('catalog_product_index_price')])
            ->where('p.website_id = ?', $websiteId)
            ->where('p.entity_id IN(?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }
}
