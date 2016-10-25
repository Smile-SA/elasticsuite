<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Catalog Data Upgrade
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
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
     * @var EavSetup
     */
    private $eavSetup;

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
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->updateCategorySearchableAttributes();
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $productImageAttributeId = $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'image');
            $setup->getConnection()->update(
                $setup->getTable('catalog_eav_attribute'),
                ['is_searchable' => 1],
                $setup->getConnection()->quoteInto('attribute_id = ?', $productImageAttributeId)
            );
        }

        $setup->endSetup();
    }

    /**
     * Update some categories attributes to have them indexed into ES.
     * Basically :
     *  - Name (indexable and searchable
     *  - Description (indexable and searchable)
     *  - Url Path (indexable)
     */
    private function updateCategorySearchableAttributes()
    {
        $setup      = $this->eavSetup->getSetup();
        $connection = $setup->getConnection();
        $table      = $setup->getTable('catalog_eav_attribute');

        // Set Name and description indexable and searchable.
        $attributeIds = [
            $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'name'),
            $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'description'),
        ];

        foreach (['is_searchable', 'is_used_in_spellcheck'] as $configField) {
            foreach ($attributeIds as $attributeId) {
                $connection->update(
                    $table,
                    [$configField => 1],
                    $connection->quoteInto('attribute_id = ?', $attributeId)
                );
            }
        }

        // Set url_path indexable.
        $urlPathAttributeId = $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'url_path');
        $connection->update(
            $table,
            ['is_searchable' => 1],
            $connection->quoteInto('attribute_id = ?', $urlPathAttributeId)
        );
    }
}
