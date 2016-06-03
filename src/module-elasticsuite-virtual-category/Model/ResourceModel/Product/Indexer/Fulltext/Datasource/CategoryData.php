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

namespace Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product\Position as ProductPositionResourceModel;

/**
 * Category datasource override. Saves product positions set from admin.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData extends \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData
{
    /**
     * @var array
     */
    private $virtualCategoriesIds;

    /**
     * {@inheritDoc}
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()->union(
            [
                $this->getBaseSelect($productIds, $storeId),
                $this->getVirtualSelect($productIds),
            ]
        );

        return $select;
    }

    /**
     * Retrieve the standard categories product data (categories ids, positions, ...).
     *
     * @param array $productIds Product ids.
     * @param int   $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    private function getBaseSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable('catalog_category_product_index')], [])
            ->joinLeft(
                ['p' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)],
                'p.product_id = cpi.product_id AND p.category_id = cpi.category_id',
                []
            )
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN(?)', $productIds)
            ->columns([
                'category_id' => 'cpi.category_id',
                'product_id'  => 'cpi.product_id',
                'is_parent'   => 'cpi.is_parent',
                'position' => 'p.position',
            ]);

        return $select;
    }

    /**
     * Retrieve the virtual categories product data (categories ids, positions, ...).
     *
     * @param array $productIds Product ids.
     *
     * @return \Zend_Db_Select
     */
    private function getVirtualSelect($productIds)
    {
        $virtualCategoriesIds = $this->getVirtualCategoriesIds();

        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)], [])
            ->where('cpi.category_id IN (?)', $virtualCategoriesIds)
            ->where('cpi.product_id IN(?)', $productIds)
            ->columns(
                [
                    'category_id' => 'cpi.category_id',
                    'product_id'  => 'cpi.product_id',
                    'is_parent'   => new \Zend_Db_Expr('0'),
                    'position'    => 'cpi.position',
                ]
            );

        return $select;
    }

    /**
     * List of the ids of the virtual categories of the site.
     *
     * @return array
     */
    private function getVirtualCategoriesIds()
    {
        if ($this->virtualCategoriesIds === null) {
            $isVirtualAttribute = $this->getIsVirtualCategoryAttribute();

            $select = $this->getConnection()->select();
            $select->from($isVirtualAttribute->getBackendTable(), ['entity_id'])
                ->where('attribute_id = ?', (int) $isVirtualAttribute->getAttributeId())
                ->where('value = ?', true)
                ->group('entity_id');

            $this->virtualCategoriesIds = $this->getConnection()->fetchCol($select);
        }

        return $this->virtualCategoriesIds;
    }

    /**
     * Retrieve the 'is_virtual_category' attribute model.
     *
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    private function getIsVirtualCategoryAttribute()
    {
        return $this->getEavConfig()->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'is_virtual_category');
    }
}
