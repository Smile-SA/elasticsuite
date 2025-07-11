<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Generic Catalog Optimizer Setup
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OptimizerSetup
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\DB\FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * Class Constructor
     *
     * @param \Magento\Framework\EntityManager\MetadataPool   $metadataPool              Metadata Pool.
     * @param \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory Field Data Converter Factory.
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory
    ) {
        $this->metadataPool              = $metadataPool;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
    }

    /**
     * Create Optimizer main table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createOptimizerTable(SchemaSetupInterface $setup)
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
    public function createOptimizerSearchContainerTable(SchemaSetupInterface $setup)
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
                ->addColumn(
                    'apply_to',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => 0],
                    'If this optimizer applies to specific entities or not.'
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

    /**
     * Adding "apply_to" column to smile_elasticsuite_optimizer_search_container table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function updateOptimizerSearchContainerTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->addColumn(
                $setup->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER),
                'apply_to',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    'nullable' => false,
                    'default'  => '0',
                    'comment'  => 'If this optimizer applies to specific entities or not.',
                ]
            );
    }

    /**
     * Change the DDL type of "apply_to" column from smile_elasticsuite_optimizer_search_container table
     * from boolean to smallint.
     * This is just in case some day, the DDL boolean type becomes mapped to a real boolean (0, 1) DB column.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function updateApplyToIntegerSearchContainerTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->modifyColumn(
                $setup->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER),
                'apply_to',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'default'  => '0',
                    'comment'  => 'If this optimizer applies to specific entities or not.',
                ]
            );
    }

    /**
     * Create table containing entity association between optimizer and category_id or search_terms.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createOptimizerLimitationTable(SchemaSetupInterface $setup)
    {
        if (!$setup->getConnection()->isTableExists($setup->getTable(OptimizerInterface::TABLE_NAME_LIMITATION))) {
            $categoryIdField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\CategoryInterface::class)->getIdentifierField();

            $optimizerCategoryTable = $setup->getConnection()
                ->newTable($setup->getTable(OptimizerInterface::TABLE_NAME_LIMITATION))
                ->addColumn(
                    OptimizerInterface::OPTIMIZER_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false],
                    'Optimizer ID'
                )
                ->addColumn(
                    'category_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true, 'default' => null],
                    'Category ID'
                )
                ->addColumn(
                    'query_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true, 'default' => null],
                    'Query ID'
                )
                ->addForeignKey(
                    $setup->getFkName(
                        OptimizerInterface::TABLE_NAME_LIMITATION,
                        OptimizerInterface::OPTIMIZER_ID,
                        OptimizerInterface::TABLE_NAME,
                        OptimizerInterface::OPTIMIZER_ID
                    ),
                    OptimizerInterface::OPTIMIZER_ID,
                    $setup->getTable(OptimizerInterface::TABLE_NAME),
                    OptimizerInterface::OPTIMIZER_ID,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        OptimizerInterface::TABLE_NAME_LIMITATION,
                        'category_id',
                        $setup->getTable('catalog_category_entity'),
                        $categoryIdField
                    ),
                    'category_id',
                    $setup->getTable('catalog_category_entity'),
                    $categoryIdField,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(OptimizerInterface::TABLE_NAME_LIMITATION, 'query_id', 'search_query', 'query_id'),
                    'query_id',
                    $setup->getTable('search_query'),
                    'query_id',
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addIndex(
                    $setup->getIdxName(
                        OptimizerInterface::TABLE_NAME_LIMITATION,
                        [OptimizerInterface::OPTIMIZER_ID, 'category_id', 'query_id'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    [OptimizerInterface::OPTIMIZER_ID, 'category_id', 'query_id'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->setComment('Search optimizer limitation Table');

            $setup->getConnection()->createTable($optimizerCategoryTable);
        }
    }

    /**
     * Upgrade legacy serialized data to JSON data.
     * Targets :
     *  - columns "config" and "rule_condition" of the smile_elasticsuite_optimizer table.
     *
     * @param \Magento\Setup\Module\DataSetup $setup Setup
     *
     * @return void
     */
    public function convertSerializedRulesToJson(\Magento\Setup\Module\DataSetup $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(
            \Magento\Framework\DB\DataConverter\SerializedToJson::class
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable(OptimizerInterface::TABLE_NAME),
            OptimizerInterface::OPTIMIZER_ID,
            OptimizerInterface::CONFIG
        );

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable(OptimizerInterface::TABLE_NAME),
            OptimizerInterface::OPTIMIZER_ID,
            OptimizerInterface::RULE_CONDITION
        );
    }
}
