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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Elasticsearch category full indexer resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Full extends Indexer
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Constructor.
     *
     * @param ResourceConnection        $resource                  Resource connection.
     * @param StoreManagerInterface     $storeManager              Store manager.
     * @param MetadataPool              $metadataPool              Metadata pool.
     * @param CategoryCollectionFactory $categoryCollectionFactory Category collection factory.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($resource, $storeManager, $metadataPool);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

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
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
         */
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addIsActiveFilter();
        $select = $categoryCollection->getSelect();

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
