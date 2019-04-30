<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Product position resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Position extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    const TABLE_NAME = 'smile_virtualcategory_catalog_category_product_position';

    /**
     * Get product positions for a given categoryId and Store Id.
     *
     * @param int $categoryId The Category Id.
     * @param int $storeId    The Store Id.
     *
     * @return array
     */
    public function getProductPositions($categoryId, $storeId)
    {
        $select = $this->getBaseSelect()
            ->where('category_id = ?', (int) $categoryId)
            ->where('store_id = ?', (int) $storeId)
            ->where('position IS NOT NULL')
            ->columns(['product_id', 'position']);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Get product blacklist for a given categoryId and Store Id.
     *
     * @param int $categoryId The Category Id.
     * @param int $storeId    The Store Id.
     *
     * @return array
     */
    public function getProductBlacklist($categoryId, $storeId)
    {
        $select = $this->getBaseSelect()
            ->columns(['product_id'])
            ->where('category_id = ?', (int) $categoryId)
            ->where('store_id = ?', (int) $storeId)
            ->where('is_blacklisted = ?', (int) true);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Load product positions for the given category.
     *
     * @param CategoryInterface|int $category Category.
     *
     * @return array
     */
    public function getProductPositionsByCategory($category)
    {
        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if (is_object($category)) {
            if ($category->getUseStorePositions()) {
                $storeId = $category->getStoreId();
            }
            $category = $category->getId();
        }

        return $this->getProductPositions($category, $storeId);
    }

    /**
     * Load blacklisted products for the given query.
     *
     * @param CategoryInterface|int $category Category.
     *
     * @return array
     */
    public function getProductBlacklistByCategory($category)
    {
        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if (is_object($category)) {
            if ($category->getUseStorePositions()) {
                $storeId = $category->getStoreId();
            }
            $category = $category->getId();
        }

        return $this->getProductBlacklist($category, $storeId);
    }

    /**
     * Save the product positions.
     *
     * @param CategoryInterface $category Saved category.
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position
     */
    public function saveProductPositions(CategoryInterface $category)
    {
        // Can be 0 if not on a store view.
        $storeId = (int) $category->getStoreId();

        // If on a store view, and no store override of positions, clean up existing store records.
        if ($storeId && !$category->getUseStorePositions()) {
            $category->setSortedProducts([]);
            $category->setBlacklistedProducts([]);
        }

        $newProductPositions  = $category->getSortedProducts();
        $blacklistedProducts  = $category->getBlacklistedProducts() ?? [];

        $deleteConditions = [
            $this->getConnection()->quoteInto('category_id = ?', (int) $category->getId()),
            $this->getConnection()->quoteInto('store_id = ?', $storeId),
        ];

        if (!empty($newProductPositions) || !empty($blacklistedProducts)) {
            $insertData        = [];
            $updatedProductIds = array_merge(array_keys($newProductPositions), $blacklistedProducts);

            foreach ($updatedProductIds as $productId) {
                $insertData[] = [
                    'category_id'    => $category->getId(),
                    'product_id'     => $productId,
                    'store_id'       => $storeId,
                    'position'       => $newProductPositions[$productId] ?? null,
                    'is_blacklisted' => in_array($productId, $blacklistedProducts),
                ];
            }

            $deleteConditions[] = $this->getConnection()->quoteInto('product_id NOT IN (?)', $updatedProductIds);
            $this->getConnection()->insertOnDuplicate($this->getMainTable(), $insertData, array_keys(current($insertData)));
        }

        $this->getConnection()->delete($this->getMainTable(), implode(' AND ', $deleteConditions));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_setMainTable(self::TABLE_NAME);
    }

    /**
     * Init a base select with the main table.
     *
     * @return \Zend_Db_Select
     */
    private function getBaseSelect()
    {
        $select = $this->getConnection()->select();
        $select->from(['main_table' => $this->getMainTable()], []);

        return $select;
    }
}
