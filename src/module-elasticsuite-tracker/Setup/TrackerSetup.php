<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Setup;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;
use Smile\ElasticsuiteTracker\Model\IndexManager;

/**
 * Generic Setup for ElasticsuiteTracker module.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Botis <botis@smile.fr>
 */
class TrackerSetup
{
    /**
     * @var IndexManager
     */
    protected $indexManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * Class Constructor
     *
     * @param IndexManager         $indexManager   Index manager.
     * @param ScopeConfigInterface $scopeConfig    Scope config.
     * @param Config               $resourceConfig ResourceConfig
     */
    public function __construct(
        IndexManager $indexManager,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig
    ) {
        $this->indexManager   = $indexManager;
        $this->scopeConfig    = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * Migrate daily indices to monthly indices.
     */
    public function migrateDailyToMonthlyIndices(): void
    {
        $this->indexManager->migrateDailyToMonthlyIndices();

        $retentionDelay = $this->scopeConfig->getValue(TrackerHelper::CONFIG_RETENTION_DELAY_XPATH);
        if ($retentionDelay != 12) {
            // Convert config value from days to months.
            $retentionDelay = (int) ceil($retentionDelay / 31);
            $this->resourceConfig->saveConfig(TrackerHelper::CONFIG_RETENTION_DELAY_XPATH, $retentionDelay);
        }
    }
}
