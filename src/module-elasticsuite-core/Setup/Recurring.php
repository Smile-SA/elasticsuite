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
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\App\Cache\Manager;

/**
 * Elasticsuite recurring setup : enable the Elasticsuite cache tag.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param \Magento\Framework\App\Cache\Manager    $cacheManager     Cache Manager
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig Deployment Config
     */
    public function __construct(Manager $cacheManager, DeploymentConfig $deploymentConfig)
    {
        $this->cacheManager     = $cacheManager;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $currentStatuses = $this->deploymentConfig->getConfigData(\Magento\Framework\App\Cache\State::CACHE_KEY) ?: [];

        if (!isset($currentStatuses[\Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::TYPE_IDENTIFIER])) {
            $this->cacheManager->setEnabled(
                [\Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::TYPE_IDENTIFIER],
                true
            );
        }
    }
}
