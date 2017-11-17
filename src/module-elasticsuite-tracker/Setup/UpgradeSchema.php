<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Tracker Module Installer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Create table for the tracking module.
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "1.1.0") < 0) {
            $setup->startSetup();

            $logTable = $setup->getConnection()->newTable($setup->getTable('elasticsuite_tracker_log_event'))
                ->addColumn(
                    'event_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '32',
                    ['nullable' => false, 'primary' => true],
                    'Event Id'
                )
                ->addColumn(
                    'date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Event date'
                )
                ->addColumn(
                    'data',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '2M',
                    ['nullable' => false],
                    'Event data'
                )
                ->addColumn(
                    'data',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '2M',
                    ['nullable' => false],
                    'Event data'
                );

            $setup->getConnection()->createTable($logTable);
        }

        $setup->endSetup();
    }
}
