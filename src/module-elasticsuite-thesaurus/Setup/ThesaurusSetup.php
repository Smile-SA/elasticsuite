<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Generic Installer for Thesaurus Module
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ThesaurusSetup
{
    /**
     * Create Thesaurus main table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createThesaurusTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ThesaurusInterface::TABLE_NAME))
            ->addColumn(
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Thesaurus Id'
            )->addColumn(
                ThesaurusInterface::NAME,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Thesaurus Name'
            )->addColumn(
                ThesaurusInterface::TYPE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Thesaurus Type'
            )->setComment('Smile ElasticSuite Thesaurus Table');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Create Thesaurus/store link table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createThesaurusStoreTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ThesaurusInterface::STORE_TABLE_NAME))
            ->addColumn(
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Thesaurus Id'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store ID'
            )->addForeignKey(
                $setup->getFkName(
                    ThesaurusInterface::STORE_TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID,
                    ThesaurusInterface::TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID
                ),
                ThesaurusInterface::THESAURUS_ID,
                $setup->getTable(ThesaurusInterface::TABLE_NAME),
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(ThesaurusInterface::STORE_TABLE_NAME, ThesaurusInterface::STORE_ID, 'store', 'store_id'),
                ThesaurusInterface::STORE_ID,
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Smile ElasticSuite Thesaurus Table for link between thesauri and stores');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Create Thesaurus/expansion reference terms link table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createExpansionReferenceTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ThesaurusInterface::REFERENCE_TABLE_NAME))
            ->addColumn(
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Thesaurus Id'
            )->addColumn(
                'term_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Reference Term Id'
            )
            ->addColumn(
                'term',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Reference Term'
            )->addForeignKey(
                $setup->getFkName(
                    ThesaurusInterface::REFERENCE_TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID,
                    ThesaurusInterface::TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID
                ),
                ThesaurusInterface::THESAURUS_ID,
                $setup->getTable(ThesaurusInterface::TABLE_NAME),
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Smile ElasticSuite Thesaurus Table for link between thesauri and reference terms');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Create Relation between Thesaurus and expanded terms (which are also synonyms) link table
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function createExpandedTermsTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME))
            ->addColumn(
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Thesaurus Id'
            )->addColumn(
                'term_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Reference Term Id'
            )
            ->addColumn(
                'term',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'primary' => true],
                'Reference Term'
            )->addIndex(
                $setup->getIdxName(ThesaurusInterface::EXPANSION_TABLE_NAME, ['term_id']),
                ['term_id']
            )->addForeignKey(
                $setup->getFkName(
                    ThesaurusInterface::EXPANSION_TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID,
                    ThesaurusInterface::TABLE_NAME,
                    ThesaurusInterface::THESAURUS_ID
                ),
                ThesaurusInterface::THESAURUS_ID,
                $setup->getTable(ThesaurusInterface::TABLE_NAME),
                ThesaurusInterface::THESAURUS_ID,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Smile ElasticSuite Thesaurus Table for link between thesauri and expanded terms');

        $setup->getConnection()->createTable($table);
    }

    /**
     * Add an "is_active" column to the Thesaurus table.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Setup instance
     */
    public function appendIsActiveColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable(ThesaurusInterface::TABLE_NAME),
            ThesaurusInterface::IS_ACTIVE,
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default'  => 1,
                'comment'  => 'If the Thesaurus is active',
            ]
        );
    }
}
