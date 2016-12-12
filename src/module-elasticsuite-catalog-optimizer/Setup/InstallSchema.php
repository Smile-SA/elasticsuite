<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Install Schema for Catalog Optimizer Module
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->createOptimizerTable($setup);
        $this->createOptimizerSearchContainerTable($setup);

        $setup->endSetup();
    }

    /**
     * Create Optimizer main table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    private function createOptimizerTable(SchemaSetupInterface $setup)
    {
        if (!$setup->getConnection()->isTableExists($setup->getTable(OptimizerInterface::TABLE_NAME))) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable(OptimizerInterface::TABLE_NAME))
                ->addColumn(
                    OptimizerInterface::OPTIMIZER_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true],
                    'Optimizer ID'
                )
                ->addColumn(
                    OptimizerInterface::STORE_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false],
                    'Store id'
                )
                ->addColumn(
                    OptimizerInterface::IS_ACTIVE,
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => '1'],
                    'Is Optimizer Active'
                )
                ->addColumn(
                    OptimizerInterface::FROM_DATE,
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    ['nullable' => true],
                    'Enable rule from date'
                )
                ->addColumn(
                    OptimizerInterface::TO_DATE,
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATE,
                    null,
                    ['nullable' => true],
                    'Enable rule to date'
                )
                ->addColumn(
                    OptimizerInterface::NAME,
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Optimizer Name'
                )
                ->addColumn(
                    OptimizerInterface::MODEL,
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Optimizer model'
                )
                ->addColumn(
                    OptimizerInterface::CONFIG,
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '',
                    [],
                    'Optimizer serialized configuration'
                )
                ->addColumn(
                    OptimizerInterface::RULE_CONDITION,
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    '',
                    [],
                    'Optimizer rule condition configuration'
                )
                ->setComment('Search optimizer Table');

            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * Create Optimizer Query table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    private function createOptimizerSearchContainerTable(SchemaSetupInterface $setup)
    {
        if (!$setup->getConnection()->isTableExists($setup->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER))) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER))
                ->addColumn(
                    OptimizerInterface::OPTIMIZER_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'primary' => true],
                    'Optimizer ID'
                )
                ->addColumn(
                    OptimizerInterface::SEARCH_CONTAINER,
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'primary' => true],
                    'Search Container'
                )
                ->addIndex(
                    $setup->getIdxName(OptimizerInterface::TABLE_NAME, [OptimizerInterface::SEARCH_CONTAINER]),
                    [OptimizerInterface::SEARCH_CONTAINER]
                )
                ->addForeignKey(
                    $setup->getFkName(
                        OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER,
                        OptimizerInterface::OPTIMIZER_ID,
                        OptimizerInterface::TABLE_NAME,
                        OptimizerInterface::OPTIMIZER_ID
                    ),
                    OptimizerInterface::OPTIMIZER_ID,
                    $setup->getTable(OptimizerInterface::TABLE_NAME),
                    OptimizerInterface::OPTIMIZER_ID,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->setComment('Query type per optimizer table');

            $setup->getConnection()->createTable($table);
        }
    }
}
