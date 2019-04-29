<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

/**
 * Interface InventoryDataInterface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
interface InventoryDataInterface
{
    /**
     * Load inventory data for a list of product ids and a given store.
     * Expected rows structure : ['product_id', 'stock_status', 'qty'].
     *
     * @param integer $storeId    Store id.
     * @param array   $productIds Product ids list.
     *
     * @return array
     */
    public function loadInventoryData($storeId, $productIds);
}
