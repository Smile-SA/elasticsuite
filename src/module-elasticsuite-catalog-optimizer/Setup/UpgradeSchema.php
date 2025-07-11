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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Elasticsuite CatalogOptimizer Schema Upgrade
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class UpgradeSchema implements UpgradeSchemaInterface
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
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "1.1.0") < 0) {
            $this->optimizerSetup->createOptimizerLimitationTable($setup);
            $this->optimizerSetup->updateOptimizerSearchContainerTable($setup);
        }
        if (version_compare($context->getVersion(), "1.2.1") < 0) {
            $this->optimizerSetup->updateApplyToIntegerSearchContainerTable($setup);
        }
        $setup->endSetup();
    }
}
