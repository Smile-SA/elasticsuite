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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position as ProductPositionResourceModel;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\CategoryInterface;

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
     * @var null|CategoryAttributeInterface
     */
    private $useStorePositionsAttribute = null;

    /**
     * {@inheritDoc}
     */
    protected function getCategoryProductSelect($productIds, $storeId)
    {
        $select = $this->getConnection()->select()->union(
            [
                $this->getBaseSelectGlobal($productIds, $storeId),
                $this->getBaseSelectStore($productIds, $storeId),
                $this->getVirtualSelectGlobal($productIds, $storeId),
                $this->getVirtualSelectStore($productIds, $storeId),
            ]
        );

        return $select;
    }

    /**
     * Retrieve the standard categories product data (categories ids, positions, ...).
     * Product positions returned are those defined globally.
     *
     * @param array $productIds Product ids.
     * @param int   $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    private function getBaseSelectGlobal($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCategoryProductIndexTable($storeId))], [])
            ->joinLeft(
                ['p' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)],
                'p.product_id = cpi.product_id AND p.category_id = cpi.category_id AND p.store_id = 0',
                []
            );

        $this->joinStorePosition($select, $storeId, 0);

        $select->where('cpi.store_id = ?', (int) $storeId)
            ->where('cpi.product_id IN(?)', $productIds)
            ->columns([
                'category_id'    => 'cpi.category_id',
                'product_id'     => 'cpi.product_id',
                'is_parent'      => 'cpi.is_parent',
                'is_virtual'     => new \Zend_Db_Expr('"false"'),
                'position'       => 'cpi.position',
                'is_blacklisted' => 'p.is_blacklisted',
            ]);

        return $select;
    }

    /**
     * Retrieve the standard categories product data (categories ids, positions, ...).
     * Product positions returned are those defined at the store level.
     *
     * @param array $productIds Product ids.
     * @param int   $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    private function getBaseSelectStore($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['cpi' => $this->getTable($this->getCategoryProductIndexTable($storeId))], [])
            ->joinLeft(
                ['p' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)],
                'p.product_id = cpi.product_id AND p.category_id = cpi.category_id AND p.store_id = cpi.store_id',
                []
            );

        $select->where('cpi.store_id = ?', (int) $storeId)
            ->where('cpi.product_id IN(?)', $productIds)
            ->columns([
                'category_id'    => 'cpi.category_id',
                'product_id'     => 'cpi.product_id',
                'is_parent'      => 'cpi.is_parent',
                'is_virtual'     => new \Zend_Db_Expr('"false"'),
                'position'       => 'cpi.position',
                'is_blacklisted' => 'p.is_blacklisted',
            ]);

        return $select;
    }

    /**
     * Retrieve the virtual categories product data (categories ids, positions, ...).
     * Product positions returned are those defined globally.
     *
     * @param array   $productIds Product ids.
     * @param integer $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    private function getVirtualSelectGlobal($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['p' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)], [])
            ->joinLeft(
                ['cpi' => $this->getTable($this->getCategoryProductIndexTable($storeId))],
                'p.product_id = cpi.product_id AND p.category_id = cpi.category_id',
                []
            );

        $this->joinStorePosition($select, $storeId, 0);

        $select->where('p.product_id IN(?)', $productIds)
            ->where('cpi.product_id IS NULL')
            ->where('p.store_id = 0')
            ->columns(
                [
                    'category_id'    => 'p.category_id',
                    'product_id'     => 'p.product_id',
                    'is_parent'      => new \Zend_Db_Expr('0'),
                    'is_virtual'     => new \Zend_Db_Expr('"true"'),
                    'position'       => 'p.position',
                    'is_blacklisted' => 'p.is_blacklisted',
                ]
            );

        return $select;
    }

    /**
     * Retrieve the virtual categories product data (categories ids, positions, ...).
     * Product positions returned are those defined locally.
     *
     * @param array   $productIds Product ids.
     * @param integer $storeId    Store id.
     *
     * @return \Zend_Db_Select
     */
    private function getVirtualSelectStore($productIds, $storeId)
    {
        $select = $this->getConnection()->select()
            ->from(['p' => $this->getTable(ProductPositionResourceModel::TABLE_NAME)], [])
            ->joinLeft(
                ['cpi' => $this->getTable($this->getCategoryProductIndexTable($storeId))],
                'p.product_id = cpi.product_id AND p.category_id = cpi.category_id',
                []
            );

        $select->where('p.product_id IN(?)', $productIds)
            ->where('cpi.product_id IS NULL')
            ->where('p.store_id = ?', (int) $storeId)
            ->columns(
                [
                    'category_id'    => 'p.category_id',
                    'product_id'     => 'p.product_id',
                    'is_parent'      => new \Zend_Db_Expr('0'),
                    'is_virtual'     => new \Zend_Db_Expr('"true"'),
                    'position'       => 'p.position',
                    'is_blacklisted' => 'p.is_blacklisted',
                ]
            );

        return $select;
    }

    /**
     * Process left join on a select to the 'use_store_positions' attribute backend table.
     *
     * @param \Magento\Framework\Db\Select $select                 The select to alter
     * @param int                          $storeId                The store id
     * @param int                          $useStorePositionsValue The value of the use_store_positions attribute to select.
     */
    private function joinStorePosition(\Magento\Framework\Db\Select &$select, $storeId, $useStorePositionsValue)
    {
        $useStorePositionsAttr  = $this->getUseStorePositionsAttribute();

        if ($useStorePositionsAttr && $useStorePositionsAttr->getAttributeId()) {
            $linkField = $this->getEntityMetaData(CategoryInterface::class)->getLinkField();

            $conditions    = [
                "c.{$linkField} = use_store_positions.{$linkField}",
                "use_store_positions.store_id = " . (int) $storeId,
                "use_store_positions.attribute_id = " . (int) $useStorePositionsAttr->getAttributeId(),
            ];
            $joinCondition = new \Zend_Db_Expr(implode(" AND ", $conditions));

            $select->joinLeft(
                ['c' => $this->getTable('catalog_category_entity')],
                'p.category_id = c.entity_id',
                []
            );

            $select->joinLeft(
                ['use_store_positions' => $useStorePositionsAttr->getBackendTable()],
                $joinCondition,
                []
            );

            $select->where(
                $this->getConnection()->quoteInto(
                    "COALESCE(use_store_positions.value, 0) = ?",
                    $useStorePositionsValue
                )
            );
        }
    }

    /**
     * Returns category attribute "use store positions"
     *
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    private function getUseStorePositionsAttribute()
    {
        $this->useStorePositionsAttribute = $this->getEavConfig()
            ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'use_store_positions');

        return $this->useStorePositionsAttribute;
    }
}
