<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Setup;

use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Eav\Setup\EavSetup;

/**
 * Schema for Catalog attributes
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var \Magento\Eav\Setup\EavSetup EAV Entity Setup
     */
    private $eavSetup;

    /**
     * InstallSchema constructor.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup The EAV Setup
     */
    public function __construct(EavSetup $eavSetup)
    {
        $this->eavSetup = $eavSetup;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Installs DB schema for the module
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    // @codingStandardsIgnoreStart Conform to interface
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        // @codingStandardsIgnoreEnd

        $connection = $setup->getConnection();
        $eavSetup = $this->eavSetup;

        $table = $setup->getTable('catalog_eav_attribute');

        // Append a column 'is_used_in_autocomplete' into the db.
        $connection->addColumn(
            $table,
            'is_used_in_autocomplete',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default'  => '1',
                'comment'  => 'If attribute is used in autocomplete',
            ]
        );

        // Enable 'is_used_in_autocomplete' by default for the attribute 'name' for product AND category.
        $attributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'name');
        $connection->update(
            $table,
            ['is_used_in_autocomplete' => 1],
            $connection->quoteInto('attribute_id = ?', $attributeId)
        );

        $attributeId = $eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'name');
        $connection->update(
            $table,
            ['is_used_in_autocomplete' => 1],
            $connection->quoteInto('attribute_id = ?', $attributeId)
        );

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
                'default'  => \Smile\ElasticSuiteCore\Search\Request\BucketInterface::SORT_ORDER_COUNT,
                'length'   => 25,
                'comment'  => 'The sort order for facet values',
            ]
        );

        $setup->endSetup();
    }
}
