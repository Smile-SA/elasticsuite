<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

/**
 * ElasticSearch product full indexer resource model.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Full extends Indexer
{
    /**
     * Load a bulk of product data.
     *
     * @param int     $storeId    Store id.
     * @param string  $productIds Product ids filter.
     * @param integer $fromId     Load product with id greater than.
     * @param integer $limit      Number of product to get loaded.
     *
     * @return array
     */
    public function getSearchableProducts($storeId, $productIds = null, $fromId = 0, $limit = 100)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getTable('catalog_product_entity')]);

        $this->addIsVisibleInStoreFilter($select, $storeId);

        if ($productIds !== null) {
            $select->where('e.entity_id IN (?)', $productIds);
        }

        $select->where('e.entity_id > ?', $fromId)
            ->limit($limit)
            ->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Filter the select to append only product visible into the catalog or search into the index.
     *
     * Note : Magento put only enabled products that have the following
     *        visibility into the catalog_category_product_index table :
     *         - visible in catalog
     *         - visible in search
     *         - visible in catalog & search
     *
     *        So joining on the root category will filter only products that have to be indexed
     *        and you don't need to put any additional filter on the visibility field.
     *
     * @param \Zend_Db_Select $select  Product select to be filtered.
     * @param integer         $storeId Store Id
     *
     * @return \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full Self Reference
     */
    private function addIsVisibleInStoreFilter($select, $storeId)
    {
        $rootCategoryId = $this->getRootCategoryId($storeId);
        $indexTable = $this->getTable('catalog_category_product_index');

        $visibilityJoinCond = $this->getConnection()->quoteInto(
            'visibility.product_id = e.entity_id AND visibility.store_id = ?',
            $storeId
        );

        $select->useStraightJoin(true)
            ->join(['visibility' => $indexTable], $visibilityJoinCond, ['visibility'])
            ->where('visibility.category_id = ?', $rootCategoryId);

        return $this;
    }
}
