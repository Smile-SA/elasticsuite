<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Catalog installer
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
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
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Create attribute on category to enable/disable name indexation for search
     *
     * Installs Data for the module
     *
     * @param ModuleDataSetupInterface $setup   The setup interface
     * @param ModuleContextInterface   $context The module Context
     */
    // @codingStandardsIgnoreStart Conform to interface
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        // @codingStandardsIgnoreEnd

        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $connection = $setup->getConnection();
        $entityTypeId = \Magento\Catalog\Model\Category::ENTITY;

        $eavSetup->addAttribute(
            $entityTypeId,
            'use_name_in_product_search',
            [
                'type'       => 'int',
                'label'      => 'Use category name in product search',
                'input'      => 'select',
                'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'required'   => true,
                'default'    => 1,
                'visible'    => true,
                'note'       => "If the category name is used for fulltext search on products.",
                'sort_order' => 150,
                'group'      => 'General Information',
            ]
        );

        $attributeId = $eavSetup->getAttributeId($entityTypeId, 'use_name_in_product_search');

        $select = $connection->select();

        $select->from(
            $setup->getTable('catalog_category_entity'),
            [
                new \Zend_Db_Expr("{$attributeId} as attribute_id"),
                'entity_id',
                new \Zend_Db_Expr("1 as value"),
            ]
        );

        $insert = $setup->getConnection()->insertFromSelect(
            $select,
            $setup->getTable('catalog_category_entity_int'),
            ['attribute_id', 'entity_id', 'value'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
        );

        $setup->getConnection()->query($insert);

        $setup->endSetup();
    }
}
