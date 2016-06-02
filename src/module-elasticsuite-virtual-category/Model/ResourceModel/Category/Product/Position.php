<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Product position resource model.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Position extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    const TABLE_NAME = 'smile_virtualcategory_catalog_category_product_position';

    /**
     * Load product positions for the given category.
     *
     * @param CategoryInterface|int $category Category.
     *
     * @return array
     */
    public function getProductPositionsByCategory($category)
    {
        if (is_object($category)) {
            $category = $category->getId();
        }

        $select = $this->getBaseSelect()
            ->where('category_id = ?', (int) $category)
            ->columns(['product_id', 'position']);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Save the product postions.
     *
     * @param CategoryInterface $category Saved category.
     *
     * @return \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product\Position
     */
    public function saveProductPositions(CategoryInterface $category)
    {
        $newProductPositions  = $category->getSortedProducts();

        $deleteConditions = [
            $this->getConnection()->quoteInto('category_id = ?', (int) $category->getId()),
        ];

        if (!empty($newProductPositions)) {
            $insertData = [];

            foreach ($newProductPositions as $productId => $position) {
                $insertData[] = [
                    'category_id' => $category->getId(),
                    'product_id'  => $productId,
                    'position'    => $position,
                ];
            }

            $deleteConditions[] = $this->getConnection()->quoteInto(
                'product_id NOT IN (?)',
                array_keys($newProductPositions)
            );
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
