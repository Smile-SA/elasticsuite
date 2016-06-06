<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Schema upgrade class for Catalog module
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface   $setup   Setup
     * @param ModuleContextInterface $context Context
     *
     * @return void
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->appendDecimalDisplayConfiguration($setup);
        }

        $setup->endSetup();
    }

    /**
     * Append decimal display related columns to attribute table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup The setup instance
     */
    private function appendDecimalDisplayConfiguration(SchemaSetupInterface $setup)
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
}
