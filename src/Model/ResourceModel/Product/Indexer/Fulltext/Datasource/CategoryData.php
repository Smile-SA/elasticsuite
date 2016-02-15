<?php

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\AbstractIndexer;

class CategoryData extends AbstractIndexer
{
    /**
     *
     * @param int   $storeId
     * @param array $productIds
     */
    public function loadCategoryData($storeId, $productIds)
    {

        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable('catalog_category_product_index')])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }
}
