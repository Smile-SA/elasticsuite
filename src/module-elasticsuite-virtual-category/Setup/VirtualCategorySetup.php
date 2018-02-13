<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Setup;

use Magento\Catalog\Model\Category;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Generic Setup class for Virtual Categories
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class VirtualCategorySetup
{
    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    private $eavConfig;

    /**
     * VirtualCategorySetup constructor.
     *
     * @param \Magento\Eav\Model\Config $eavConfig EAV Config.
     */
    public function __construct(\Magento\Eav\Model\Config $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Create virtual categories attributes.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     */
    public function createVirtualCategoriesAttributes($eavSetup)
    {
        $eavSetup->addAttribute(
            Category::ENTITY,
            'is_virtual_category',
            [
                'type'       => 'int',
                'label'      => 'Is virtual category',
                'input'      => null,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'   => false,
                'default'    => 0,
                'visible'    => true,
                'note'       => "Is the category is virtual or not ?",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );

        $eavSetup->addAttribute(
            Category::ENTITY,
            'virtual_category_root',
            [
                'type'       => 'int',
                'label'      => 'Virtual category root',
                'input'      => null,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'   => false,
                'default'    => 0,
                'visible'    => true,
                'note'       => "Root display of the virtual category (usefull to display a facet category on virtual).",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );

        $eavSetup->addAttribute(
            Category::ENTITY,
            'virtual_rule',
            [
                'type'       => 'text',
                'label'      => 'Virtual rule',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'backend'    => 'Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\Backend\VirtualRule',
                'required'   => false,
                'default'    => null,
                'visible'    => true,
                'note'       => "Virtual category rule.",
                'sort_order' => 210,
                'group'      => 'General Information',
            ]
        );

        // Force the frontend input to be null for these attributes since they are managed by code.
        $eavSetup->updateAttribute(Category::ENTITY, 'is_virtual_category', 'frontend_input', null);
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_category_root', 'frontend_input', null);
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_rule', 'frontend_input', null);

        // Mandatory to ensure next installers will have proper EAV Attributes definitions.
        $this->eavConfig->clear();
    }

    /**
     * Migration from 1.0.0 to 1.1.0 :
     *   - Updating the attribute virtual_category_root from type varchar to type int
     *   - Updating the value of the attribute from 'category/13' to '13.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return $this
     */
    public function updateVirtualCategoryRootTypeToInt(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $setup = $eavSetup->getSetup();

        // Fix the attribute type.
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_category_root', 'backend_type', 'int');

        // Retrieve information about the attribute and storage config.
        $virtualRootAttributeId = $eavSetup->getAttribute(Category::ENTITY, 'virtual_category_root', 'attribute_id');

        $originalTable = $setup->getTable('catalog_category_entity_varchar');
        $targetTable   = $setup->getTable('catalog_category_entity_int');

        $baseFields = array_slice(array_keys($setup->getConnection()->describeTable($originalTable)), 1, -1);

        // Select old value.
        $valueSelect = $setup->getConnection()->select();
        $valueSelect->from($setup->getTable('catalog_category_entity_varchar'), $baseFields)
            ->where('attribute_id = ?', $virtualRootAttributeId)
            ->columns(['value' => new \Zend_Db_Expr('REPLACE(value, "category/", "")')]);

        // Insert old values into the new table.
        $query = $setup->getConnection()->insertFromSelect(
            $valueSelect,
            $targetTable,
            array_merge($baseFields, ['value']),
            AdapterInterface::INSERT_IGNORE
        );
        $setup->getConnection()->query($query);

        // Delete old value.
        $setup->getConnection()->delete($originalTable, "attribute_id = {$virtualRootAttributeId}");

        return $this;
    }

    /**
     * Create table containing position of products in virtual categories.
     *
     * @param SchemaSetupInterface $setup Schema Setup Interface
     */
    public function createPositionTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'smile_virtualcategory_catalog_category_product_position'
         */
        $tableName = 'smile_virtualcategory_catalog_category_product_position';
        $table = $setup->getConnection()
            ->newTable($setup->getTable($tableName))
            ->addColumn(
                'category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                'Category ID'
            )
            ->addColumn(
                'product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                'Product ID'
            )
            ->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Position'
            )
            ->addIndex($setup->getIdxName($tableName, ['product_id']), ['product_id'])
            ->addForeignKey(
                $setup->getFkName($tableName, 'category_id', 'catalog_category_entity', 'entity_id'),
                'category_id',
                $setup->getTable('catalog_category_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName($tableName, 'product_id', 'catalog_product_entity', 'entity_id'),
                'product_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Catalog product position for the virtual categories module.');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Add 'is_blacklisted' column to 'smile_virtualcategory_catalog_category_product_position'.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup interface
     */
    public function addBlacklistColumnToPositionTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('smile_virtualcategory_catalog_category_product_position'),
            'is_blacklisted',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => 0,
                'comment'  => 'If the product is blacklisted',
            ]
        );
    }
}
