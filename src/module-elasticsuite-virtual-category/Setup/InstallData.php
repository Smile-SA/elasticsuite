<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Smile\ElasticsuiteVirtualCategory\Setup\VirtualCategorySetupFactory;

/**
 * Catalog installer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
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
     * @var VirtualCategorySetup
     */
    private $virtualCategorySetup;

    /**
     * Class Constructor
     *
     * @param EavSetupFactory             $eavSetupFactory             Eav setup factory.
     * @param VirtualCategorySetupFactory $virtualCategorySetupFactory Virtual Category setup factory.
     */
    public function __construct(EavSetupFactory $eavSetupFactory, VirtualCategorySetupFactory $virtualCategorySetupFactory)
    {
        $this->eavSetupFactory      = $eavSetupFactory;
        $this->virtualCategorySetup = $virtualCategorySetupFactory->create();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Append virtual category related attributes  :
     * - is_virtual_category
     * - virtual_rule
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

        $this->virtualCategorySetup->createVirtualCategoriesAttributes($eavSetup);

        $setup->endSetup();
    }
}
