<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Simon Sippert <s.sippert@techdivision.com>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Setup;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Catalog uninstaller.
 *
 * Reverts the changes CatalogSetup::updateDateAttributes() made to the product attributes
 * 'created_at' and 'updated_at'. Without the HideProductAttributesPlugin of this module they would
 * otherwise be rendered in the product form, and saving a product would overwrite 'created_at'.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Simon Sippert <s.sippert@techdivision.com>
 */
class Uninstall implements UninstallInterface
{
    /**
     * Product attributes made visible by CatalogSetup::updateDateAttributes().
     *
     * @var string[]
     */
    private $dateAttributes = ['created_at', 'updated_at'];

    /**
     * Revert the changes this module made to core product attributes.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SchemaSetupInterface   $setup   Setup.
     * @param ModuleContextInterface $context Module context.
     *
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->resetDateAttributes($setup);
        $setup->endSetup();
    }

    /**
     * Restore the core definition of 'created_at' and 'updated_at' (hidden, no label).
     *
     * @param SchemaSetupInterface $setup Setup.
     *
     * @return void
     */
    private function resetDateAttributes(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $entityTypeId = $connection->fetchOne(
            $connection->select()
                ->from($setup->getTable('eav_entity_type'), ['entity_type_id'])
                ->where('entity_type_code = ?', Product::ENTITY)
        );

        if (!$entityTypeId) {
            return;
        }

        $attributeIds = $connection->fetchCol(
            $connection->select()
                ->from($setup->getTable('eav_attribute'), ['attribute_id'])
                ->where('entity_type_id = ?', $entityTypeId)
                ->where('attribute_code IN (?)', $this->dateAttributes)
        );

        if (empty($attributeIds)) {
            return;
        }

        $connection->update(
            $setup->getTable('eav_attribute'),
            ['frontend_label' => null],
            ['attribute_id IN (?)' => $attributeIds]
        );

        $connection->update(
            $setup->getTable('catalog_eav_attribute'),
            ['is_visible' => 0],
            ['attribute_id IN (?)' => $attributeIds]
        );
    }
}
