<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Smile\ElasticsuiteVirtualCategory\Setup\VirtualCategorySetupFactory;

/**
 * Catalog data upgrade.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var VirtualCategorySetup
     */
    private $virtualCategorySetup;

    /**
     * Class Constructor
     *
     * @param EavSetupFactory             $eavSetupFactory             Eav setup factory.
     * @param VirtualCategorySetupFactory $virtualCategorySetupFactory Virtual category Setup.
     */
    public function __construct(EavSetupFactory $eavSetupFactory, VirtualCategorySetupFactory $virtualCategorySetupFactory)
    {
        $this->eavSetupFactory      = $eavSetupFactory;
        $this->virtualCategorySetup = $virtualCategorySetupFactory->create();
    }

    /**
     * Upgrade the module data.
     *
     * @param ModuleDataSetupInterface $setup   The setup interface
     * @param ModuleContextInterface   $context The module Context
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->virtualCategorySetup->updateVirtualCategoryRootTypeToInt($this->eavSetupFactory->create(['setup' => $setup]));
        }

        $setup->endSetup();
    }
}
