<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
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

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->createCustomerLinkTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Create customer link table.
     *
     * @param SchemaSetupInterface $setup Setup
     */
    private function createCustomerLinkTable(SchemaSetupInterface $setup)
    {
        $logTable = $setup->getConnection()->newTable($setup->getTable('elasticsuite_tracker_log_customer_link'))
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Customer ID'
            )
            ->addColumn(
                'session_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Session ID'
            )
            ->addColumn(
                'visitor_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Visitor ID'
            )
            ->addColumn(
                'delete_after',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => true, 'default' => null],
                'Delete after'
            )->addForeignKey(
                $setup->getFkName('elasticsuite_tracker_log_customer_link', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $setup->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Smile ElasticSuite Tracker customer link Table');

        $setup->getConnection()->createTable($logTable);
    }
}
