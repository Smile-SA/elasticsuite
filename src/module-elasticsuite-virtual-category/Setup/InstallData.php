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
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

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
     * Class Constructor
     *
     * @param EavSetupFactory $eavSetupFactory Eav setup factory.
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
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

        $eavSetup->addAttribute(
            Category::ENTITY,
            'is_virtual_category',
            [
                'type'       => 'int',
                'label'      => 'Is virtual category',
                'input'      => null,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'   => false,
                'default'    => 0,
                'visible'    => true,
                'note'       => "Is the category is virtual or not ?",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );

        $eavSetup->addAttribute(
            Category::ENTITY,
            'virtual_category_root',
            [
                'type'       => 'int',
                'label'      => 'Virtual category root',
                'input'      => null,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'   => false,
                'default'    => 0,
                'visible'    => true,
                'note'       => "Root display of the virtual category (usefull to display a facet category on virtual).",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );

        $eavSetup->addAttribute(
            Category::ENTITY,
            'virtual_rule',
            [
                'type'       => 'text',
                'label'      => 'Virtual rule',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'backend'    => 'Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\Backend\VirtualRule',
                'required'   => false,
                'default'    => null,
                'visible'    => true,
                'note'       => "Virtual category rule.",
                'sort_order' => 210,
                'group'      => 'General Information',
            ]
        );

        // Force the frontend input to be null for these attributes since they are managed by code.
        $eavSetup->updateAttribute(Category::ENTITY, 'is_virtual_category', 'frontend_input', null);
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_category_root', 'frontend_input', null);
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_rule', 'frontend_input', null);

        $setup->endSetup();
    }
}
