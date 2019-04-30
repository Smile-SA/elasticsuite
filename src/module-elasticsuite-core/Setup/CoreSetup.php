<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Generic installer for ElasticsuiteCore
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CoreSetup
{
    /**
     * Create Relevance Config table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup Interface
     */
    public function createRelevanceConfigTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        /**
         * Create table 'smile_elasticsuite_relevance_config_data'
         */
        $table = $connection->newTable(
            $setup->getTable('smile_elasticsuite_relevance_config_data')
        )->addColumn(
            'config_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Config Id'
        )->addColumn(
            'scope',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => false, 'default' => 'default'],
            'Config Scope'
        )->addColumn(
            'scope_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            30,
            ['nullable' => false, 'default' => 'default'],
            'Config Scope Code'
        )->addColumn(
            'path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => 'general'],
            'Config Path'
        )->addColumn(
            'value',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Config Value'
        )->addIndex(
            $setup->getIdxName(
                'smile_elasticsuite_relevance_config_data',
                ['scope', 'scope_id', 'path'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['scope', 'scope_code', 'path'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->setComment(
            'Smile ElasticSuite Relevance Config Data'
        );
        $connection->createTable($table);
    }
}
