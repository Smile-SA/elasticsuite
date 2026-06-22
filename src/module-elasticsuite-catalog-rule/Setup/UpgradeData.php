<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Upgrade the module data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CatalogRuleSetup
     */
    private CatalogRuleSetup $catalogRuleSetup;

    /**
     * Constructor.
     *
     * @param CatalogRuleSetupFactory $catalogRuleSetupFactory Catalog rule setup factory.
     */
    public function __construct(CatalogRuleSetupFactory $catalogRuleSetupFactory)
    {
        $this->catalogRuleSetup = $catalogRuleSetupFactory->create();
    }

    /**
     * Upgrade the module data.
     *
     * @param ModuleDataSetupInterface $setup   The setup interface.
     * @param ModuleContextInterface   $context The module context.
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->catalogRuleSetup->migrateAttributes($setup);
        }

        $setup->endSetup();
    }
}
