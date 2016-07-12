<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position as ProductPositionResourceModel;

/**
 * Category datasource override. Saves product positions set from admin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData extends \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData
{
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
                'is_virtual'  => new \Zend_Db_Expr(0),
                'position'    => 'p.position',
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
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)], [])
            ->where('cpi.product_id IN(?)', $productIds)
            ->columns(
                [
                    'category_id' => 'cpi.category_id',
                    'product_id'  => 'cpi.product_id',
                    'is_parent'   => new \Zend_Db_Expr('0'),
                    'is_virtual'  => new \Zend_Db_Expr('1'),
                    'position'    => 'cpi.position',
                ]
            );

        return $select;
    }
}
