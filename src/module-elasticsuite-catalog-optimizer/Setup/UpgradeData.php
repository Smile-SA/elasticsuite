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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Upgrade Data for Smile Elasticsuite Optimizer module.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
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
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "1.2.0") < 0) {
            $this->optimizerSetup->convertSerializedRulesToJson($setup);
        }
        $setup->endSetup();
    }
}
