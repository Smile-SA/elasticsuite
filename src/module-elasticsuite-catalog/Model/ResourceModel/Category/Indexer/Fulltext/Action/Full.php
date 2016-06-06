<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Indexer\Fulltext\Action;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

/**
 * ElasticSearch category full indexer resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Full extends Indexer
{
    /**
     * Load a bulk of category data.
     *
     * @param int     $storeId     Store id.
     * @param string  $categoryIds Product ids filter.
     * @param integer $fromId      Load product with id greater than.
     * @param integer $limit       Number of product to get loaded.
     *
     * @return array
     */
    public function getSearchableCategories($storeId, $categoryIds = null, $fromId = 0, $limit = 100)
    {
        $select = $this->getConnection()->select()
            ->from(['e' => $this->getTable('catalog_category_entity')]);

        $this->addIsVisibleInStoreFilter($select, $storeId);

        if ($categoryIds !== null) {
            $select->where('e.entity_id IN (?)', $categoryIds);
        }

        $select->where('e.entity_id > ?', $fromId)
            ->limit($limit)
            ->order('e.entity_id');

        return $this->connection->fetchAll($select);
    }

    /**
     * Filter the select to append only categories that are childrens of the root category of current store.
     *
     * @param \Zend_Db_Select $select  Product select to be filtered.
     * @param integer         $storeId Store Id
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full Self Reference
     */
    private function addIsVisibleInStoreFilter($select, $storeId)
    {
        $rootCategoryId = $this->getRootCategoryId($storeId);

        $select->where('e.path LIKE ?', "1/{$rootCategoryId}%");

        return $this;
    }
}
