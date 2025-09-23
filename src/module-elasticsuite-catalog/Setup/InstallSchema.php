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
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Setup;

use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Schema for Catalog attributes
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
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
     * Installs DB schema for the module
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->catalogSetup->addEavCatalogFields($setup);
        $this->catalogSetup->addIsSpellcheckedToSearchQuery($setup);

        // Introduced in version 1.1.0.
        $this->catalogSetup->appendDecimalDisplayConfiguration($setup);

        // Introduced in version 1.3.0.
        $this->catalogSetup->createCategoryFacetConfigurationTable($setup);

        // Introduced in version 1.4.0.
        $this->catalogSetup->createSearchPositionTable($setup);

        // Introduced in version 1.5.1.
        $this->catalogSetup->addSortOrderMissingFields($setup);

        // Introduced in version 1.6.0.
        $this->catalogSetup->addFilterBooleanLogicField($setup);

        // Introduced in version 1.6.1.
        $this->catalogSetup->addIncludeZeroFalseValues($setup);

        // Introduced in version 1.7.0.
        $this->catalogSetup->addIsSpannableAttributeProperty($setup);
        $this->catalogSetup->addNormsDisabledAttributeProperty($setup);
        $this->catalogSetup->addDefaultAnalyzer($setup);

        // Introduced in version 1.8.0.
        $this->catalogSetup->addScoringAlgorithm($setup);

        $setup->endSetup();
    }
}
