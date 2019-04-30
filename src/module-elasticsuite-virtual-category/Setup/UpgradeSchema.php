<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Smile\ElasticsuiteVirtualCategory\Setup\VirtualCategorySetupFactory;

/**
 * Upgrade Schema for virtual categories
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var VirtualCategorySetup
     */
    private $virtualCategorySetup;

    /**
     * InstallSchema constructor.
     *
     * @param VirtualCategorySetupFactory $virtualCategorySetupFactory Virtual Category Setup Factory
     */
    public function __construct(VirtualCategorySetupFactory $virtualCategorySetupFactory)
    {
        $this->virtualCategorySetup = $virtualCategorySetupFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->virtualCategorySetup->addBlacklistColumnToPositionTable($setup);
            $this->virtualCategorySetup->setNullablePositionColumn($setup);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->virtualCategorySetup->addStoreIdColumnToPositionTable($setup);
        }

        $setup->endSetup();
    }
}
