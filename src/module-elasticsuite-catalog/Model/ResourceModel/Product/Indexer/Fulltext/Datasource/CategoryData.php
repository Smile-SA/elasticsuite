<?php
/**
 * DISCLAIMER
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

use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;

/**
 * Categories data datasource resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData extends Indexer
{
    /**
     * @var array Local cache for category names
     */
    private $categoryNameCache = [];

    /**
     * @var null|CategoryAttributeInterface
     */
    private $categoryNameAttribute = null;

    /**
     * @var null|CategoryAttributeInterface
     */
    private $useNameInSearchAttribute = null;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig = null;

    /**
     * CategoryData constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection     $resource     Connection Resource
     * @param \Magento\Store\Model\StoreManagerInterface    $storeManager The store manager
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool Metadata Pool
     * @param \Magento\Eav\Model\Config                     $eavConfig    EAV Configuration
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
        parent::__construct($resource, $storeManager, $metadataPool);
    }

    /**
     * Load categories data for a list of product ids and a given store.
     *
     * @param integer $storeId    Store id.
     * @param array   $productIds Product ids list.
     *
     * @return array
     */
    public function loadCategoryData($storeId, $productIds)
    {
        $select       = $this->getCategoryProductSelect($productIds, $storeId);
        $categoryData = $this->getConnection()->fetchAll($select);

        $categoryIds = [];

        foreach ($categoryData as $categoryDataRow) {
            $categoryIds[] = $categoryDataRow['category_id'];
        }

        $storeCategoryName = $this->loadCategoryNames(array_unique($categoryIds), $storeId);

        foreach ($categoryData as &$categoryDataRow) {
            $categoryDataRow['name'] = '';
            if (isset($storeCategoryName[(int) $categoryDataRow['category_id']])) {
                $categoryDataRow['name'] = $storeCategoryName[(int) $categoryDataRow['category_id']];
            }
        }

        return $categoryData;
    }

    /**
     * Prepare indexed data select.
     *
     * @param array   $productIds Product ids.
     * @param integer $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable('catalog_category_product_index')])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds);

        return $select;
    }

    /**
     * Returns category name attribute
     *
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    protected function getCategoryNameAttribute()
    {
        $this->categoryNameAttribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'name');

        return $this->categoryNameAttribute;
    }

    /**
     * Returns category "use name in product search" attribute
     *
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    protected function getUseNameInSearchAttribute()
    {
        $this->useNameInSearchAttribute = $this->eavConfig
            ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'use_name_in_product_search');

        return $this->useNameInSearchAttribute;
    }

    /**
     * Access to EAV configuration.
     *
     * @return \Magento\Eav\Model\Config
     */
    protected function getEavConfig()
    {
        return $this->eavConfig;
    }

    /**
     * Add some categories name into the cache of names of categories.
     *
     * @param array $categoryIds Ids of the categories to be added to the cache.
     * @param int   $storeId     Store Id
     *
     * @return array
     */
    private function loadCategoryNames($categoryIds, $storeId)
    {
        $loadCategoryIds = $categoryIds;

        if (isset($this->categoryNameCache[$storeId])) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryNameCache[$storeId]));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);

        if (!empty($loadCategoryIds)) {
            $select = $this->prepareCategoryNameSelect($loadCategoryIds, $storeId);
            $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();

            foreach ($this->getConnection()->fetchAll($select) as $row) {
                $categoryId = (int) $row[$entityIdField];
                $this->categoryNameCache[$storeId][$categoryId] = '';
                if ((bool) $row['use_name']) {
                    $this->categoryNameCache[$storeId][$categoryId] = $row['name'];
                }
            }
        }

        return isset($this->categoryNameCache[$storeId]) ? $this->categoryNameCache[$storeId] : [];
    }

    /**
     * Prepare SQL query to retrieve category names
     *
     * @param array $loadCategoryIds Category ids to load name for
     * @param int   $storeId         The current store Id
     *
     * @return \Magento\Framework\DB\Select
     */
    private function prepareCategoryNameSelect($loadCategoryIds, $storeId)
    {
        $rootCategoryId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $this->categoryNameCache[$storeId][$rootCategoryId] = '';

        $nameAttr      = $this->getCategoryNameAttribute();
        $useNameAttr   = $this->getUseNameInSearchAttribute();
        $entityIdField = $this->getEntityMetaData(CategoryInterface::class)->getIdentifierField();
        $linkField     = $this->getEntityMetaData(CategoryInterface::class)->getLinkField();
        $select        = $this->connection->select();

        $conditions    = [
            "cat.{$linkField} = default_value.{$linkField}",
            "default_value.store_id=0",
            "default_value.attribute_id = " . (int) $nameAttr->getAttributeId(),
        ];

        $joinCondition = new \Zend_Db_Expr(implode(" AND ", $conditions));
        $select->from(['cat' => $this->getEntityMetaData(CategoryInterface::class)->getEntityTable()], [$entityIdField])
            ->joinLeft(['default_value' => $nameAttr->getBackendTable()], $joinCondition, [])
            ->where("cat.$entityIdField != ?", $rootCategoryId)
            ->where("cat.$entityIdField IN (?)", $loadCategoryIds);

        // Join to check for use_name_in_product_search.
        $joinUseNameCond = sprintf(
            "default_value.$linkField = use_name_default_value.$linkField" .
            " AND use_name_default_value.attribute_id = %d AND use_name_default_value.store_id = %d",
            (int) $useNameAttr->getAttributeId(),
            0
        );
        $select->joinLeft(['use_name_default_value' => $useNameAttr->getBackendTable()], $joinUseNameCond, []);

        if ($this->storeManager->isSingleStoreMode()) {
            $select->columns(['name' => 'default_value.value']);
            $select->columns(['use_name' => 'COALESCE(use_name_default_value.value,1)']);

            return $select;
        }

        // Multi store additional join to get scoped name value.
        $joinStoreNameCond = sprintf(
            "cat.$linkField = store_value.$linkField" .
            " AND store_value.attribute_id = %d AND store_value.store_id = %d",
            (int) $nameAttr->getAttributeId(),
            (int) $storeId
        );
        $select->joinLeft(['store_value' => $nameAttr->getBackendTable()], $joinStoreNameCond, [])
            ->columns(['name' => 'COALESCE(store_value.value,default_value.value, "")']);

        // Multi store additional join to get scoped "use_name_in_product_search" value.
        $joinUseNameStoreCond = sprintf(
            "cat.$linkField = use_name_store_value.$linkField" .
            " AND use_name_store_value.attribute_id = %d AND use_name_store_value.store_id = %d",
            (int) $useNameAttr->getAttributeId(),
            (int) $storeId
        );
        $select->joinLeft(['use_name_store_value' => $useNameAttr->getBackendTable()], $joinUseNameStoreCond, [])
            ->columns(['use_name' => 'COALESCE(use_name_store_value.value,use_name_default_value.value,1)']);

        return $select;
    }
}
