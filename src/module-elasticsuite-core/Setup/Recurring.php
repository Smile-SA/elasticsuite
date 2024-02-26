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
     * @param \Magento\Framework\App\Cache\Manager $cacheManager Cache Manager
     */
    public function __construct(Manager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * {@inheritDoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->cacheManager->setEnabled(
            [\Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::TYPE_IDENTIFIER],
            true
        );
    }
}
