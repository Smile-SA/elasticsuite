<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Tracker Data Upgrade.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Botis <botis@smile.fr>
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var TrackerSetup
     */
    private $trackerSetup;

    /**
     * Class Constructor.
     *
     * @param TrackerSetupFactory $trackerSetupFactory Tracker setup factory.
     */
    public function __construct(TrackerSetupFactory $trackerSetupFactory)
    {
        $this->trackerSetup    = $trackerSetupFactory->create();
    }

    /**
     * Upgrade the module data.
     *
     * @param ModuleDataSetupInterface $setup   The setup interface
     * @param ModuleContextInterface   $context The module Context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->trackerSetup->migrateDailyToMonthlyIndices();
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->trackerSetup->addOrderItemDateToEventMapping();
        }

        $setup->endSetup();
    }
}
