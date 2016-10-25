<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Setup;

use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Schema for Catalog attributes
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for the module
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->addEavCatalogFields($setup);
        $this->addIsSpellcheckedToSearchQuery($setup);
        $setup->endSetup();
    }

    /**
     * Add custom fields to catalog_eav_attribute table.
     *
     * @param SchemaSetupInterface $setup The setup interface
     *
     * @return void
     */
    private function addEavCatalogFields(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

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
    private function addIsSpellcheckedToSearchQuery(SchemaSetupInterface $setup)
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
}
