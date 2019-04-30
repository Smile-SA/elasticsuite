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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Install Schema for Thesaurus Module
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var ThesaurusSetup
     */
    private $thesaurusSetup;

    /**
     * InstallSchema constructor.
     *
     * @param \Smile\ElasticsuiteThesaurus\Setup\ThesaurusSetupFactory $thesaurusSetupFactory Setup Factory
     */
    public function __construct(ThesaurusSetupFactory $thesaurusSetupFactory)
    {
        $this->thesaurusSetup = $thesaurusSetupFactory->create();
    }

    /**
     * Installs DB schema for a module
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SchemaSetupInterface   $setup   Setup
     * @param ModuleContextInterface $context Context
     *
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $this->thesaurusSetup->createThesaurusTable($setup);
            $this->thesaurusSetup->createThesaurusStoreTable($setup);
            $this->thesaurusSetup->createExpandedTermsTable($setup);
            $this->thesaurusSetup->createExpansionReferenceTable($setup);
        }

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $this->thesaurusSetup->appendIsActiveColumn($setup);
        }

        $setup->endSetup();
    }
}
