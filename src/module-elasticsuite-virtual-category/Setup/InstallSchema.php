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

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteVirtualCategory\Setup\VirtualCategorySetupFactory;

/**
 * Schema for virtual categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
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
     * Installs DB schema for the module
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->virtualCategorySetup->createPositionTable($setup);

        $setup->endSetup();
    }
}
