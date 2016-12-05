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
     * Class Constructor
     *
     * @param EavSetupFactory $eavSetupFactory Eav setup factory.
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
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
            $this->updateVirtualCategoryRootTypeToInt($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addGenerateVirtualCategorySubtreeAttribute($setup);
        }

        $setup->endSetup();
    }

    /**
     * Migration from 1.0.0 to 1.1.0 :
     *   - Updating the attribute virtual_category_root from type varchar to type int
     *   - Updating the value of the attribute from 'category/13' to '13.
     *
     * @param ModuleDataSetupInterface $setup Setup.
     *
     * @return $this
     */
    private function updateVirtualCategoryRootTypeToInt(ModuleDataSetupInterface $setup)
    {
        /**
         * @var \Magento\Eav\Setup\EavSetup $eavSetup
         */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // Fix the attribute type.
        $eavSetup->updateAttribute(Category::ENTITY, 'virtual_category_root', 'backend_type', 'int');

        // Retrieve information about the attribute and storage config.
        $virtualRootAttributeId = $eavSetup->getAttribute(Category::ENTITY, 'virtual_category_root', 'attribute_id');

        $originalTable = $setup->getTable('catalog_category_entity_varchar');
        $targetTable   = $setup->getTable('catalog_category_entity_int');

        $baseFields = array_slice(array_keys($setup->getConnection()->describeTable($originalTable)), 1, -1);

        // Select old value.
        $valueSelect = $setup->getConnection()->select();
        $valueSelect->from($setup->getTable('catalog_category_entity_varchar'), $baseFields)
            ->where('attribute_id = ?', $virtualRootAttributeId)
            ->columns(['value' => new \Zend_Db_Expr('REPLACE(value, "category/", "")')]);

        // Insert old values into the new table.
        $query = $setup->getConnection()->insertFromSelect(
            $valueSelect,
            $targetTable,
            array_merge($baseFields, ['value']),
            AdapterInterface::INSERT_IGNORE
        );
        $setup->getConnection()->query($query);

        // Delete old value.
        $setup->getConnection()->delete($originalTable, "attribute_id = {$virtualRootAttributeId}");

        return $this;
    }

    /**
     * Append the "generate_root_category_subtree" attribute to categories
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup Setup.
     */
    private function addGenerateVirtualCategorySubtreeAttribute(ModuleDataSetupInterface $setup)
    {
        /**
         * @var \Magento\Eav\Setup\EavSetup $eavSetup
         */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Category::ENTITY,
            'generate_root_category_subtree',
            [
                'type'       => 'int',
                'label'      => 'Generate Virtual Category Subtree',
                'input'      => null,
                'global'     => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'required'   => false,
                'default'    => 0,
                'visible'    => true,
                'note'       => "If the subtree of this virtual category should be displayed into category search filter",
                'sort_order' => 200,
                'group'      => 'General Information',
            ]
        );
    }
}
