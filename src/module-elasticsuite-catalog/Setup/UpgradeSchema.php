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
     * @var \Smile\ElasticsuiteCatalog\Setup\CatalogSetup
     */
    private $catalogSetup;

    /**
     * InstallSchema constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Setup\CatalogSetupFactory $catalogSetupFactory ElasticsuiteCatalog Setup.
     */
    public function __construct(CatalogSetupFactory $catalogSetupFactory)
    {
        $this->catalogSetup = $catalogSetupFactory->create();
    }

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
            $this->catalogSetup->appendDecimalDisplayConfiguration($setup);
        }

        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $this->catalogSetup->removeIsUsedInAutocompleteField($setup);
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->catalogSetup->createCategoryFacetConfigurationTable($setup);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->catalogSetup->createSearchPositionTable($setup);
        }

        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $this->catalogSetup->addBlacklistColumnToSearchPositionTable($setup);
        }

        $setup->endSetup();
    }
}
