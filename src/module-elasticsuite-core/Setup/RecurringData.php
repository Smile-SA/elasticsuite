<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Catalog Data Upgrade
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RecurringData implements InstallDataInterface
{
    /**
     *
     * @var \Magento\Deploy\Model\DeploymentConfig\Hash
     */
    private $configHash;

    /**
     * Constructor.
     *
     * @param \Magento\Deploy\Model\DeploymentConfig\Hash $configHash Config hash.
     */
    public function __construct(\Magento\Deploy\Model\DeploymentConfig\Hash $configHash)
    {
        $this->configHash = $configHash;
    }

    /**
     * {@inheritDoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->configHash->regenerate();

        $setup->endSetup();
    }
}
