<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
            $this->catalogSetup->setNullablePositionColumn($setup);
        }

        if (version_compare($context->getVersion(), '1.5.1', '<')) {
            $this->catalogSetup->addSortOrderMissingFields($setup);
        }

        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->catalogSetup->addFilterBooleanLogicField($setup);
        }

        if (version_compare($context->getVersion(), '1.6.1', '<')) {
            $this->catalogSetup->addIsDisplayRelNofollowColumn($setup);
        }

        if (version_compare($context->getVersion(), '1.6.2', '<')) {
            $this->catalogSetup->addIncludeZeroFalseValues($setup);
        }

        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $this->catalogSetup->addIsSpannableAttributeProperty($setup);
            $this->catalogSetup->addNormsDisabledAttributeProperty($setup);
            $this->catalogSetup->addDefaultAnalyzer($setup);
        }

        if (version_compare($context->getVersion(), '1.8.0', '<')) {
            $this->catalogSetup->addScoringAlgorithm($setup);
        }

        $setup->endSetup();
    }
}
