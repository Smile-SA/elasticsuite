<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Core Data Upgrade.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CoreSetup
     */
    private $coreSetup;

    /**
     * Class Constructor.
     *
     * @param CoreSetupFactory $coreSetupFactory Core setup factory.
     */
    public function __construct(CoreSetupFactory $coreSetupFactory)
    {
        $this->coreSetup = $coreSetupFactory->create();
    }

    /**
     * Upgrade the module data.
     *
     * @param ModuleDataSetupInterface $setup   The setup interface.
     * @param ModuleContextInterface   $context The module context.
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $this->coreSetup->updateDefaultIndicesPattern();
        }

        $setup->endSetup();
    }
}
