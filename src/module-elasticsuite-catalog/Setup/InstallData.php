<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Indexer\IndexerInterfaceFactory;

/**
 * Catalog installer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Smile\ElasticsuiteCatalog\Setup\CatalogSetup
     */
    private $catalogSetup;

    /**
     * @var \Smile\ElasticsuiteCatalog\Setup\IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * Class Constructor
     *
     * @param EavSetupFactory         $eavSetupFactory     Eav setup factory.
     * @param CatalogSetupFactory     $catalogSetupFactory Catalog Setup factory.
     * @param IndexerInterfaceFactory $indexerFactory      Indexer Factory.
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CatalogSetupFactory $catalogSetupFactory,
        IndexerInterfaceFactory $indexerFactory
    ) {
        $this->catalogSetup    = $catalogSetupFactory->create();
        $this->eavSetupFactory = $eavSetupFactory;
        $this->indexerFactory  = $indexerFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Installs Data for the module :
     *  - Create attribute on category to enable/disable name indexation for search
     *  - Update is anchor attribute (hidden frontend input, null source model, enabled by default).
     *
     * @param ModuleDataSetupInterface $setup   The setup interface
     * @param ModuleContextInterface   $context The module Context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $this->catalogSetup->addCategoryNameSearchAttribute($eavSetup);
        $this->catalogSetup->updateCategoryIsAnchorAttribute($eavSetup);
        $this->catalogSetup->updateDefaultValuesForNameAttributes($eavSetup);
        $this->catalogSetup->updateCategorySearchableAttributes($eavSetup);
        $this->catalogSetup->updateImageAttribute($eavSetup);

        $this->getIndexer('elasticsuite_categories_fulltext')->reindexAll();

        $setup->endSetup();
    }

    /**
     * Retrieve an indexer by its Id
     *
     * @param string $indexerId The indexer Id
     *
     * @return \Magento\Framework\Indexer\IndexerInterface
     */
    private function getIndexer($indexerId)
    {
        return $this->indexerFactory->create()->load($indexerId);
    }
}
