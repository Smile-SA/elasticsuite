<?php

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\AbstractIndexer;

class Full extends AbstractIndexer
{
    /**
     *
     * @param int $storeId
     * @param string $productIds
     * @param int $lastProductId
     * @param int $limit
     *
     * @return array
     */
    public function getSearchableProducts($storeId, $productIds = null, $lastProductId = 0, $limit = 100)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getTable('catalog_product_entity')]);

        $this->addIsVisibleInStoreFilter($select, $storeId);

        if ($productIds !== null) {
            $select->where('e.entity_id IN (?)', $productIds);
        }

        $select->where('e.entity_id > ?', $lastProductId)
            ->limit($limit)
            ->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Filter the select to append only product visible into the catalog or search into the index.
     *
     * Note : Magento put only enabled products that have the following visibility into the catalog_category_product_index table :
     *         - visible in catalog
     *         - visible in search
     *         - visible in catalog & search
     *
     *        So joining on the root category will filter only products that have to be indexed and you don't need to put any
     *        additional filter on the visibility field.
     *
     * @todo : Dynamic root category id by store.
     *
     * @param \Zend_Db_Select $select Product select to be filtered.
     * @param int $storeId Store Id
     *
     * @return \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full Self Reference
     */
    private function addIsVisibleInStoreFilter($select, $storeId)
    {
        $rootCategoryId = $this->getRootCategoryId($storeId);

        $visibilityJoinCond = $this->getConnection()->quoteInto(
            'visibility.product_id = e.entity_id AND visibility.store_id = ?', $storeId
        );

        $select->useStraightJoin(true)
            ->join(['visibility' => $this->getTable('catalog_category_product_index')], $visibilityJoinCond, ['visibility'])
            ->where('visibility.category_id = ?', $rootCategoryId);

        return $this;
    }
}