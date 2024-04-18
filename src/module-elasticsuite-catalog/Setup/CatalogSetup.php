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
