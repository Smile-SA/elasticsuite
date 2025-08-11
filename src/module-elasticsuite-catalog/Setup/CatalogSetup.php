<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Generic Setup for ElasticsuiteCatalog module.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * Class Constructor
     *
     * @param MetadataPool    $metadataPool    Metadata Pool.
     * @param Config          $eavConfig       EAV Config.
     * @param IndexerRegistry $indexerRegistry Indexer Registry
     */
    public function __construct(MetadataPool $metadataPool, Config $eavConfig, IndexerRegistry $indexerRegistry)
    {
        $this->metadataPool    = $metadataPool;
        $this->eavConfig       = $eavConfig;
        $this->indexerRegistry = $indexerRegistry;
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
     * Create attribute on category to enable/disable displaying category in autocomplete results.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function addIsDisplayCategoryInAutocompleteAttribute($eavSetup)
    {
        // Installing the new attribute.
        $eavSetup->addAttribute(
            Category::ENTITY,
            'is_displayed_in_autocomplete',
            [
                'type'       => 'int',
                'label'      => 'Display Category in Autocomplete',
                'input'      => 'select',
                'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'required'   => true,
                'default'    => 1,
                'visible'    => true,
                'note'       => "If the category can be displayed in autocomplete results.",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );

        // Set the attribute value to 1 for all existing categories.
        $this->updateCategoryAttributeDefaultValue(
            $eavSetup,
            Category::ENTITY,
            'is_displayed_in_autocomplete',
            1
        );

        // Mandatory to ensure next installers will have proper EAV Attributes definitions.
        $this->eavConfig->clear();
    }

    /**
     * Create attribute on category to change the sort direction per category.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function addSortDirectionAttribute($eavSetup)
    {
        // Installing the new attribute.
        $eavSetup->addAttribute(
            Category::ENTITY,
            'sort_direction',
            [
                'type'       => 'varchar',
                'label'      => 'Sort Direction',
                'input'      => 'select',
                'source'     => \Smile\ElasticsuiteCatalog\Model\Category\Attribute\Source\SortDirection::class,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'required'   => false,
                'default'    => 'asc',
                'visible'    => true,
                'group'      => 'Display Settings',
                'sort_order' => 110,
            ]
        );

        // Set the attribute value to 'asc' for all existing categories.
        $this->updateCategoryAttributeDefaultValue(
            $eavSetup,
            Category::ENTITY,
            'sort_direction',
            'asc'
        );

        // Mandatory to ensure next installers will have proper EAV Attributes definitions.
        $this->eavConfig->clear();
    }

    /**
     * Update Display Category in Autocomplete attribute to have it indexed into ES.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     */
    public function updateIsDisplayInAutocompleteAttribute($eavSetup)
    {
        $setup      = $eavSetup->getSetup();
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Set is_displayed_in_autocomplete indexable.
        $isDisplayedInAutocompletePathAttributeId = $eavSetup->getAttributeId(
            \Magento\Catalog\Model\Category::ENTITY,
            'is_displayed_in_autocomplete'
        );
        $connection->update(
            $table,
            ['is_searchable' => 1],
            $connection->quoteInto('attribute_id = ?', $isDisplayedInAutocompletePathAttributeId)
        );

        $fulltextCategoriesIndex = $this->indexerRegistry->get(\Smile\ElasticsuiteCatalog\Model\Category\Indexer\Fulltext::INDEXER_ID);
        $fulltextCategoriesIndex->invalidate();
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
     * Update default values for the sku field of product entity.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function updateDefaultValuesForSkuAttribute($eavSetup)
    {
        $setup      = $eavSetup->getSetup();
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        $attributeIds = [
            $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'sku'),
        ];

        foreach ($attributeIds as $attributeId) {
            $connection->update(
                $table,
                ['default_analyzer' => FieldInterface::ANALYZER_REFERENCE],
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
     * Add "sort_order_asc_missing" and "sort_order_desc_missing" fields to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     */
    public function addSortOrderMissingFields(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'sort_order_asc_missing' into the db.
        $connection->addColumn(
            $table,
            'sort_order_asc_missing',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => \Smile\ElasticsuiteCore\Search\Request\SortOrderInterface::MISSING_LAST,
                'length'   => 10,
                'comment'  => 'Sort products without value when sorting ASC',
            ]
        );

        // Append a column 'sort_order_desc_missing' into the db.
        $connection->addColumn(
            $table,
            'sort_order_desc_missing',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => \Smile\ElasticsuiteCore\Search\Request\SortOrderInterface::MISSING_FIRST,
                'length'   => 10,
                'comment'  => 'Sort products without value when sorting DESC',
            ]
        );
    }

    /**
     * Add "facet_boolean_logic" field to catalog_eav_attribute table.
     * Allows to select the logical operator for combining multiple values of an active filterable attribute in the layer navigation
     * (catalog and search) as well as in API requests.
     * Does NOT apply to catalog rules' (virtual categories and search optimizers) "is one of"/"is not one of" conditions.
     * The "OR" logical operator is the legacy Elasticsuite behavior and thus the default: selecting two values will still
     * mean "value1 OR value2" ~ "values1 or value2 or both".
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addFilterBooleanLogicField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'facet_boolean_logic' into the db.
        $connection->addColumn(
            $table,
            'facet_boolean_logic',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default'  => \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface::FILTER_LOGICAL_OPERATOR_OR,
                'length'   => null,
                'comment'  => 'Boolean logic to use when combining multiple selected values inside the filter',
                'after'    => 'facet_sort_order',
            ]
        );
    }

    /**
     * Add "is_display_rel_nofollow" column to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addIsDisplayRelNofollowColumn(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'is_display_rel_nofollow' into the db.
        $connection->addColumn(
            $table,
            'is_display_rel_nofollow',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => '0',
                'comment'  => 'Boolean logic to use for displaying rel="nofollow" attribute for all filter links of current attribute',
                'after'    => 'facet_boolean_logic',
            ]
        );
    }

    /**
     * Add "include_zero_false_values" field to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addIncludeZeroFalseValues(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'include_zero_false_values' into the db.
        $connection->addColumn(
            $table,
            'include_zero_false_values',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => 0,
                'size'     => 1,
                'comment'  => 'Should the search engine index zero (integer or decimal attribute) or false (boolean attribute) values',
            ]
        );
    }

    /**
     * Add "is_spannable" field to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addIsSpannableAttributeProperty(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'is_spannable' into the db.
        $connection->addColumn(
            $table,
            'is_spannable',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => 0,
                'size'     => 1,
                'comment'  => 'Should this field be used for span queries.',
            ]
        );
    }

    /**
     * Add "norms_disabled" field to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addNormsDisabledAttributeProperty(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'norms_disabled' into the db.
        $connection->addColumn(
            $table,
            'norms_disabled',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => 0,
                'size'     => 1,
                'comment'  => 'If this field should have norms:false in Elasticsearch.',
            ]
        );
    }

    /**
     * Add "default_analyzer" field to catalog_eav_attribute table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Schema Setup
     *
     * @return void
     */
    public function addDefaultAnalyzer(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Append a column 'default_analyzer' into the db.
        $connection->addColumn(
            $table,
            'default_analyzer',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => (string) FieldInterface::ANALYZER_STANDARD,
                'length'   => 30,
                'comment'  => 'Default analyzer for this field',
            ]
        );
    }

    /**
     * Update 'created_at' and 'updated_at' attributes and set them to editable in the back-office.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup EAV module Setup
     *
     * @return void
     */
    public function updateDateAttributes($eavSetup)
    {
        $setup      = $eavSetup->getSetup();
        $connection = $setup->getConnection();
        $eavAttributeTable = $setup->getTable('eav_attribute');
        $catalogEavAttributeTable = $setup->getTable('catalog_eav_attribute');

        $attributesToUpdate = [
            'created_at',
            'updated_at',
        ];

        foreach ($attributesToUpdate as $attributeCode) {
            $attributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);

            // Update eav_attribute table.
            $connection->update(
                $eavAttributeTable,
                [
                    'is_user_defined' => 0,
                    'is_required'     => 0,
                    'frontend_label'  => ($attributeCode == 'created_at') ? 'Created At' : 'Updated At',
                ],
                $connection->quoteInto('attribute_id = ?', $attributeId)
            );

            // Update catalog_eav_attribute table.
            $connection->update(
                $catalogEavAttributeTable,
                ['is_visible' => 1],
                $connection->quoteInto('attribute_id = ?', $attributeId)
            );
        }
    }

    /**
     * Clear the search terms listing ui component stored settings to allow new columns/new positions to be taken
     * into account correctly.
     *
     * @param ModuleDataSetupInterface $setup Data setup.
     *
     * @return void
     */
    public function clearSearchTermListingUiBookmarks(ModuleDataSetupInterface $setup): void
    {
        $select = $setup->getConnection()->select()
            ->from($setup->getTable('ui_bookmark'))
            ->where('namespace = ?', 'search_term_listing');

        $setup->getConnection()->deleteFromSelect($select, $setup->getTable('ui_bookmark'));
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
            [
                new \Zend_Db_Expr("{$attributeId} as attribute_id"),
                $linkField,
                new \Zend_Db_Expr((is_string($value) ? "'{$value}'" : $value) . ' as value'),
            ]
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
