<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Generic Setup for ElasticsuiteCatalog module.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CatalogSetup
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * Class Constructor
     *
     * @param MetadataPool $metadataPool Metadata Pool.
     * @param Config       $eavConfig    EAV Config.
     */
    public function __construct(MetadataPool $metadataPool, Config $eavConfig)
    {
        $this->metadataPool    = $metadataPool;
        $this->eavConfig       = $eavConfig;
    }

    /**
     * Create attribute on category to enable/disable name indexation for search.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function addCategoryNameSearchAttribute($eavSetup)
    {
        // Installing the new attribute.
        $eavSetup->addAttribute(
            Category::ENTITY,
            'use_name_in_product_search',
            [
                'type'       => 'int',
                'label'      => 'Use category name in product search',
                'input'      => 'select',
                'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'required'   => true,
                'default'    => 1,
                'visible'    => true,
                'note'       => "If the category name is used for fulltext search on products.",
                'sort_order' => 150,
                'group'      => 'General Information',
            ]
        );

        // Set the attribute value to 1 for all existing categories.
        $this->updateCategoryAttributeDefaultValue($eavSetup, Category::ENTITY, 'use_name_in_product_search', 1);

        // Mandatory to ensure next installers will have proper EAV Attributes definitions.
        $this->eavConfig->clear();
    }

    /**
     * Update is anchor attribute (hidden frontend input, null source model, enabled by default).
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function updateCategoryIsAnchorAttribute($eavSetup)
    {
        $eavSetup->updateAttribute(Category::ENTITY, 'is_anchor', 'frontend_input', 'hidden');
        $eavSetup->updateAttribute(Category::ENTITY, 'is_anchor', 'source_model', null);
        $categoryTreeRootId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        $this->updateCategoryAttributeDefaultValue($eavSetup, Category::ENTITY, 'is_anchor', 1, [$categoryTreeRootId]);
    }

    /**
     * Update default values for the name field of category and product entities.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function updateDefaultValuesForNameAttributes($eavSetup)
    {
        $setup      = $eavSetup->getSetup();
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        $attributeIds = [
            $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'name'),
            $eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'name'),
        ];

        foreach ($attributeIds as $attributeId) {
            $connection->update(
                $table,
                ['is_used_in_spellcheck' => true],
                $connection->quoteInto('attribute_id = ?', $attributeId)
            );
        }
    }

    /**
     * Add custom fields to catalog_eav_attribute table.
     *
     * @param SchemaSetupInterface $setup The setup interface
     *
     * @return void
     */
    public function addEavCatalogFields(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'is_displayed_in_autocomplete' into the db.
        $connection->addColumn(
            $table,
            'is_displayed_in_autocomplete',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => '0',
                'comment'  => 'If attribute is displayed in autocomplete',
            ]
        );

        // Append a column 'is_used_in_spellcheck' to the table.
        $connection->addColumn(
            $table,
            'is_used_in_spellcheck',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => '1',
                'comment'  => 'If fuzziness is used on attribute',
            ]
        );

        // Append facet_min_coverage_rate to the table.
        $connection->addColumn(
            $table,
            'facet_min_coverage_rate',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => false,
                'default'  => 90,
                'comment'  => 'Facet min coverage rate',
            ]
        );

        // Append facet_max_size to the table.
        $connection->addColumn(
            $table,
            'facet_max_size',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => false,
                'default'  => '10',
                'comment'  => 'Facet max size',
            ]
        );

        // Append facet_sort_order to the table.
        $connection->addColumn(
            $table,
            'facet_sort_order',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => \Smile\ElasticsuiteCore\Search\Request\BucketInterface::SORT_ORDER_COUNT,
                'length'   => 25,
                'comment'  => 'The sort order for facet values',
            ]
        );
    }

    /**
     * Append is spellchecked to the search query report table.
     *
     * @param SchemaSetupInterface $setup The setup interface
     *
     * @return void
     */
    public function addIsSpellcheckedToSearchQuery(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('search_query');
        $connection->addColumn(
            $table,
            'is_spellchecked',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => '0',
                'comment'  => 'Is the query spellchecked',
            ]
        );
    }

    /**
     * Append decimal display related columns to attribute table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup The setup instance
     */
    public function appendDecimalDisplayConfiguration(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'display_pattern' into the db.
        $connection->addColumn(
            $table,
            'display_pattern',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'length'   => 10,
                'comment'  => 'The pattern to display facet values',
            ]
        );

        // Append a column 'display_precision' into the db.
        $connection->addColumn(
            $table,
            'display_precision',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'default'  => 0,
                'comment'  => 'Attribute decimal precision for display',
            ]
        );
    }

    /**
     * Remove the "is_used_in_autocomplete"
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup The setup instance
     */
    public function removeIsUsedInAutocompleteField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        $connection->dropColumn($table, 'is_used_in_autocomplete');
    }

    /**
     * Update Product 'image' attribute and set it searchable.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     */
    public function updateImageAttribute($eavSetup)
    {
        $productImageAttributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'image');
        $setup = $eavSetup->getSetup();

        $setup->getConnection()->update(
            $setup->getTable('catalog_eav_attribute'),
            ['is_used_for_promo_rules' => 1, 'is_searchable' => 0, 'is_used_in_spellcheck' => 0],
            $setup->getConnection()->quoteInto('attribute_id = ?', $productImageAttributeId)
        );
    }


    /**
     * Update some categories attributes to have them indexed into ES.
     * Basically :
     *  - Name (indexable and searchable
     *  - Description (indexable and searchable)
     *  - Url Path (indexable)
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     */
    public function updateCategorySearchableAttributes($eavSetup)
    {
        $setup      = $eavSetup->getSetup();
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Set Name and description indexable and searchable.
        $attributeIds = [
            $eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'name'),
            $eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'description'),
        ];

        foreach (['is_searchable', 'is_used_in_spellcheck'] as $configField) {
            foreach ($attributeIds as $attributeId) {
                $connection->update(
                    $table,
                    [$configField => 1],
                    $connection->quoteInto('attribute_id = ?', $attributeId)
                );
            }
        }

        // Set url_path indexable.
        $urlPathAttributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'url_path');
        $connection->update(
            $table,
            ['is_searchable' => 1],
            $connection->quoteInto('attribute_id = ?', $urlPathAttributeId)
        );
    }

    /**
     * Create table containing configuration of facets for categories.
     *
     * @param SchemaSetupInterface $setup Schema Setup Interface
     */
    public function createCategoryFacetConfigurationTable(SchemaSetupInterface $setup)
    {
        // Create table 'smile_elasticsuitecatalog_category_filterable_attribute'.
        $tableName = 'smile_elasticsuitecatalog_category_filterable_attribute';
        $idField   = $this->metadataPool->getMetadata(CategoryInterface::class)->getIdentifierField();

        $table = $setup->getConnection()
            ->newTable($setup->getTable($tableName))
            ->addColumn(
                $idField,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Category ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false],
                'Attribute Id'
            )
            ->addColumn(
                'position',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'unsigned' => true],
                'Position'
            )
            ->addColumn(
                'facet_display_mode',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Facet display mode'
            )
            ->addColumn(
                'facet_min_coverage_rate',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => true],
                'Facet min coverage rate'
            )
            ->addColumn(
                'facet_max_size',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'unsigned' => true],
                'Facet max size'
            )
            ->addColumn(
                'facet_sort_order',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                25,
                ['nullable' => true],
                'Facet sort order'
            )
            ->addForeignKey(
                $setup->getFkName($tableName, $idField, 'catalog_category_entity', $idField),
                $idField,
                $setup->getTable('catalog_category_entity'),
                $idField,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName($tableName, 'attribute_id', 'eav_attribute', 'attribute_id'),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Facet configuration for each category.');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Create table 'smile_elasticsuitecatalog_search_query_product_position'.
     *
     * @param SchemaSetupInterface $setup Setup.
     *
     * @return void
     */
    public function createSearchPositionTable(SchemaSetupInterface $setup)
    {
        $tableName = 'smile_elasticsuitecatalog_search_query_product_position';
        $table = $setup->getConnection()
        ->newTable($setup->getTable($tableName))
        ->addColumn(
            'query_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'],
            'Query ID'
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
            $setup->getFkName($tableName, 'query_id', 'search_query', 'query_id'),
            'query_id',
            $setup->getTable('search_query'),
            'query_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )
        ->addForeignKey(
            $setup->getFkName($tableName, 'product_id', 'catalog_product_entity', 'entity_id'),
            'product_id',
            $setup->getTable('catalog_product_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )
        ->setComment('Catalog product position in search queries');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Add 'is_blacklisted' column to 'smile_elasticsuitecatalog_search_query_product_position'.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup interface
     */
    public function addBlacklistColumnToSearchPositionTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('smile_elasticsuitecatalog_search_query_product_position'),
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
     * Set the 'position' column of 'smile_elasticsuitecatalog_search_query_product_position' to nullable=true.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup interface
     */
    public function setNullablePositionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->modifyColumn(
            $setup->getTable('smile_elasticsuitecatalog_search_query_product_position'),
            'position',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'Position',
            ]
        );
    }

    /**
     * Update attribute value for an entity with a default value.
     * All existing values are erased by the new value.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup     EAV module Setup
     * @param integer|string              $entityTypeId Target entity id.
     * @param integer|string              $attributeId  Target attribute id.
     * @param mixed                       $value        Value to be set.
     * @param array                       $excludedIds  List of categories that should not be updated during the
     *                                                  process.
     *
     * @return void
     */
    private function updateCategoryAttributeDefaultValue($eavSetup, $entityTypeId, $attributeId, $value, $excludedIds = [])
    {
        $setup          = $eavSetup->getSetup();
        $entityTable    = $setup->getTable($eavSetup->getEntityType($entityTypeId, 'entity_table'));
        $attributeTable = $eavSetup->getAttributeTable($entityTypeId, $attributeId);
        $connection     = $setup->getConnection();

        if (!is_int($attributeId)) {
            $attributeId = $eavSetup->getAttributeId($entityTypeId, $attributeId);
        }

        // Retrieve the primary key name. May differs if the staging module is activated or not.
        $linkField = $this->metadataPool->getMetadata(CategoryInterface::class)->getLinkField();

        $entitySelect = $connection->select();
        $entitySelect->from(
            $entityTable,
            [new \Zend_Db_Expr("{$attributeId} as attribute_id"), $linkField, new \Zend_Db_Expr("{$value} as value")]
        );

        if (!empty($excludedIds)) {
            $entitySelect->where("entity_id NOT IN(?)", $excludedIds);
        }

        $insertQuery = $connection->insertFromSelect(
            $entitySelect,
            $attributeTable,
            ['attribute_id', $linkField, 'value'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($insertQuery);
    }
}
