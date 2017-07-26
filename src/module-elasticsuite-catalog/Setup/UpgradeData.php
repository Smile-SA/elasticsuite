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
     * @var CatalogSetup
     */
    private $catalogSetup;

    /**
     * Class Constructor
     *
     * @param EavSetupFactory     $eavSetupFactory     Eav setup factory.
     * @param CatalogSetupFactory $catalogSetupFactory Eav setup factory.
     */
    public function __construct(EavSetupFactory $eavSetupFactory, CatalogSetupFactory $catalogSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->catalogSetup    = $catalogSetupFactory->create();
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
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->catalogSetup->updateCategorySearchableAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $this->catalogSetup->updateImageAttribute($eavSetup);
        }

        $setup->endSetup();
    }
}
