<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category;

/**
 * Product attribute collection with category filter config override.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
{
    /**
     * @var string
     */
    const CATEGORY_FILTER_CONFIG_TABLE = 'smile_elasticsuitecatalog_category_filterable_attribute';

    /**
     * @var \Magento\Catalog\Api\Data\CategoryInterface
     */
    private $category;

    /**
     * @var array
     */
    private $overridenColumns = ['position', 'facet_max_size', 'facet_sort_order', 'facet_min_coverage_rate'];

    /**
     * Set the current category for the collection.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Current category.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\Collection
     */
    public function setCategory(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Apply current category settings to the collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\Collection
     */
    public function applyCategory()
    {
        $this->joinCategoryData();
        $this->removeAdditionalTableDuplicateFields();
        $this->getSelect()->columns($this->getAdditionalColumns());

        $subSelect = new \Zend_Db_Expr(sprintf("(%s)", (string) $this->getSelect()));

        $this->_select = $this->getConnection()->select()->from($subSelect);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        $this->applyCategory();
    }

    /**
     * Add the category data (left join on the table containing per category config).
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\Collection
     */
    private function joinCategoryData()
    {
        $table = $this->getTable(self::CATEGORY_FILTER_CONFIG_TABLE);

        $joinCondition = [
            'fal.attribute_id = main_table.attribute_id',
            $this->getConnection()->quoteInto('fal.entity_id = ?', (int) $this->category->getId()),
        ];

        $this->joinLeft(['fal' => $table], new \Zend_Db_Expr(implode(' AND ', $joinCondition)), []);

        return $this;
    }

    /**
     * List of additional columns added to the select.
     *
     * @return array
     */
    private function getAdditionalColumns()
    {
        $columns = ['facet_display_mode' => 'fal.facet_display_mode'];

        foreach ($this->overridenColumns as $column) {
            $columns[$column] = new \Zend_Db_Expr("COALESCE(fal.$column, additional_table.$column)");
            $columns['use_default_' . $column] = new \Zend_Db_Expr("IF(fal.$column IS NULL, 1, 0)");
            $columns['default_' . $column] = "additional_table.$column";
        }

        $maxPosition = $this->getMaxPosition() + 1;
        $columns['position'] = new \Zend_Db_Expr("IF(fal.position IS NULL, {$maxPosition} + additional_table.position, fal.position)");

        return $columns;
    }

    /**
     * Return the max attribute position configured for the current category.
     *
     * @return int
     */
    private function getMaxPosition()
    {
        $fullTableName = $this->getResource()->getTable(self::CATEGORY_FILTER_CONFIG_TABLE);
        $categoryPositionSelect = $this->getConnection()->select()
            ->from($fullTableName, [])
            ->columns(['category_max_position' => new \Zend_Db_Expr('MAX(position)')])
            ->where($this->getConnection()->quoteInto('entity_id = ?', (int) $this->category->getId()));

        return (int) $this->getConnection()->fetchOne($categoryPositionSelect);
    }

    /**
     * Clean collection select to remove duplicated fields.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\Collection
     */
    private function removeAdditionalTableDuplicateFields()
    {
        $columns = $this->getSelect()->getPart(\Zend_Db_Select::COLUMNS);

        foreach ($columns as $idx => $column) {
            if ($column[0] == 'additional_table' && $column[1] == "*") {
                unset($columns[$idx]);
            }
        }

        $this->getSelect()->setPart(\Zend_Db_Select::COLUMNS, $columns);

        foreach ($this->getAdditionalTableFields() as $fieldName) {
            $this->getSelect()->columns([$fieldName => sprintf("additional_table.%s", $fieldName)]);
        }

        return $this;
    }

    /**
     * List of columns of the additional attribute config table without the overriden fields.
     *
     * @return array
     */
    private function getAdditionalTableFields()
    {
        $type  = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY);
        $table = $type->getAdditionalAttributeTable();
        $fullTableName = $this->getResource()->getTable($table);
        $tableDesc = $this->getConnection()->describeTable($fullTableName);
        $tableFields = array_keys($tableDesc);

        return array_diff($tableFields, $this->overridenColumns);
    }
}
