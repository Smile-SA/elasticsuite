<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCatalogOptimizer\Setup\OptimizerSetupFactory;

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
     * @var OptimizerSetup
     */
    private $optimizerSetup;

    /**
     * InstallSchema constructor.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Setup\OptimizerSetupFactory $optimizerSetupFactory Setup Factory
     */
    public function __construct(OptimizerSetupFactory $optimizerSetupFactory)
    {
        $this->optimizerSetup = $optimizerSetupFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->optimizerSetup->createOptimizerTable($setup);
        $this->optimizerSetup->createOptimizerSearchContainerTable($setup);

        $setup->endSetup();
    }
}
