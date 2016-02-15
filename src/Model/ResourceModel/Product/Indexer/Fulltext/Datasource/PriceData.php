<?php

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\AbstractIndexer;

class PriceData extends AbstractIndexer
{
    /**
     *
     * @param int   $storeId
     * @param array $productIds
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
