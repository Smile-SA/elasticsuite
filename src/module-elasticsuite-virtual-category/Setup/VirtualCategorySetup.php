<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Setup;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Generic Setup class for Virtual Categories
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class VirtualCategorySetup
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    private $eavConfig;

    /**
     * @var \Magento\Framework\DB\FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * @var \Magento\Framework\DB\Select\QueryModifierFactory
     */
    private $queryModifierFactory;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat
     */
    private $flatCategoryIndexState;

    /**
     * VirtualCategorySetup constructor.
     *
     * @param \Magento\Framework\EntityManager\MetadataPool      $metadataPool              Metadata Pool.
     * @param \Magento\Eav\Model\Config                          $eavConfig                 EAV Config.
     * @param \Magento\Framework\DB\FieldDataConverterFactory    $fieldDataConverterFactory Field Data converter factory.
     * @param \Magento\Framework\DB\Select\QueryModifierFactory  $queryModifierFactory      Query Modifier Factory.
     * @param \Magento\Framework\Indexer\IndexerRegistry         $indexerRegistry           Indexer Registry.
     * @param \Magento\Catalog\Model\Indexer\Category\Flat\State $flatCategoryIndexState    Category flat index state.
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory,
        \Magento\Framework\DB\Select\QueryModifierFactory $queryModifierFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $flatCategoryIndexState
    ) {
        $this->metadataPool              = $metadataPool;
        $this->eavConfig                 = $eavConfig;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
        $this->queryModifierFactory      = $queryModifierFactory;
        $this->indexerRegistry           = $indexerRegistry;
        $this->flatCategoryIndexState    = $flatCategoryIndexState;
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
                'default'    => null,
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

        $this->addUseStorePositionsAttribute($eavSetup);

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

        $fields = $setup->getConnection()->describeTable($originalTable);

        // We will not use the auto increment field (row_id / entity_id) in the insert from select query.
        unset($fields[$setup->getConnection()->getAutoIncrementField($originalTable)]);
        // "value" field will be replaced on the flight when building the query.
        unset($fields['value']);

        $baseFields = array_keys($fields);

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
     * Migration from 1.4.0 to 1.4.1 :
     *   - Updating the attribute virtual_category_root default value from 0 to NULL
     *   - Deleting the attribute storage table rows with 0 as value
     *     (new default value of NULL means no rows in storage table for newly created categories)
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return $this
     */
    public function updateVirtualCategoryRootDefaultValue(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $setup = $eavSetup->getSetup();

        // Fix the attribute default value.
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_category_root', 'default_value', null);

        // Retrieve information about the attribute and storage config.
        $virtualRootAttributeId = $eavSetup->getAttribute(Category::ENTITY, 'virtual_category_root', 'attribute_id');

        $targetTable = $setup->getTable('catalog_category_entity_int');

        // Delete rows with the old default value.
        $setup->getConnection()->delete(
            $targetTable,
            [
                "attribute_id = {$virtualRootAttributeId}",
                "value = 0",
            ]
        );

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
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => true],
                'Position'
            )
            ->addColumn(
                'is_blacklisted',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => '0'],
                'If the product is blacklisted'
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
            ->addForeignKey(
                $setup->getFkName($tableName, 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
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

    /**
     * Set the 'position' column of 'smile_virtualcategory_catalog_category_product_position' to nullable=true.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup interface
     */
    public function setNullablePositionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->modifyColumn(
            $setup->getTable('smile_virtualcategory_catalog_category_product_position'),
            'position',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'Position',
            ]
        );
    }

    /**
     * Add 'store_id' column to 'smile_virtualcategory_catalog_category_product_position'
     * and make it part of the table compound primary key.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup interface
     */
    public function addStoreIdColumnToPositionTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('smile_virtualcategory_catalog_category_product_position');

        $setup->getConnection()->addColumn(
            $tableName,
            'store_id',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default'  => 0,
                'comment'  => 'Store ID',
                'after'    => 'product_id',
            ]
        );

        $primaryKeyName = $setup->getConnection()->getPrimaryKeyName($tableName);
        // The existing primary key will be dropped.
        $setup->getConnection()->addIndex(
            $tableName,
            $primaryKeyName,
            ['category_id', 'product_id', 'store_id'],
            AdapterInterface::INDEX_TYPE_PRIMARY
        );

        $setup->getConnection()->addForeignKey(
            $setup->getFkName($tableName, 'store_id', $setup->getTable('store'), 'store_id'),
            $tableName,
            'store_id',
            $setup->getTable('store'),
            'store_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        );
    }

    /**
     * Remove the backend model of the 'virtual_rule' attribute.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function updateVirtualRuleBackend(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        // Fix the attribute backend model.
        $eavSetup->updateAttribute(
            Category::ENTITY,
            'virtual_rule',
            'backend_model',
            null
        );
    }

    /**
     * Upgrade legacy serialized data to JSON data.
     * Targets :
     *  - the catalog_category_entity_text for the virtual_rule attribute only
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV Setup
     *
     * @return void
     */
    public function convertSerializedRulesToJson(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $setup = $eavSetup->getSetup();

        $fieldDataConverter = $this->fieldDataConverterFactory->create(
            \Magento\Framework\DB\DataConverter\SerializedToJson::class
        );

        $attributeId    = $eavSetup->getAttribute(Category::ENTITY, 'virtual_rule', 'attribute_id');
        $attributeTable = $eavSetup->getAttributeTable(Category::ENTITY, $attributeId);

        $queryModifier = $this->queryModifierFactory->create(
            'in',
            ['values' => ['attribute_id' => $attributeId]]
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $attributeTable,
            'value_id',
            'value',
            $queryModifier
        );

        $this->reindexFlatCategories();
    }

    /**
     * Add the attribute handling the per-store merchandiser
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV Setup
     *
     * @return void
     */
    public function addUseStorePositionsAttribute(\Magento\Eav\Setup\EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Category::ENTITY,
            'use_store_positions',
            [
                'type'       => 'int',
                'input'      => 'select',
                'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'label'      => 'Use store positions',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'required'   => true,
                'default'    => 0,
                'visible'    => true,
                'note'       => "Use store positions.",
                'sort_order' => 220,
                'group'      => 'General Information',
            ]
        );

        // Mandatory to ensure next installers will have proper EAV Attributes definitions.
        $this->eavConfig->clear();
    }

    /**
     * Process full reindexing of flat categories if enabled and not scheduled.
     */
    private function reindexFlatCategories()
    {
        if ($this->flatCategoryIndexState->isFlatEnabled()) {
            $flatCategoryIndexer = $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Category\Flat\State::INDEXER_ID);
            $flatCategoryIndexer->reindexAll();
        }
    }
}
