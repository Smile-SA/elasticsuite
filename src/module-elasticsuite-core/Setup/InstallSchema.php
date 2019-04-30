<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Smile\ElasticsuiteCore\Setup\CoreSetupFactory;

/**
 * Core Module Installer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var CoreSetup
     */
    private $coreSetup;

    /**
     * InstallSchema constructor.
     *
     * @param \Smile\ElasticsuiteCore\Setup\CoreSetupFactory $coreSetupFactory Core Setup Factory
     */
    public function __construct(CoreSetupFactory $coreSetupFactory)
    {
        $this->coreSetup = $coreSetupFactory->create();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface   $setup   The setup interface
     * @param ModuleContextInterface $context The module Context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->coreSetup->createRelevanceConfigTable($setup);

        $setup->endSetup();
    }
}
