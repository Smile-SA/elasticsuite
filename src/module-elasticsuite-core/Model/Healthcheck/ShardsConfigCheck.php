<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Exception;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * Class ShardsConfigCheck.
 *
 * Checks for shard misconfigurations in the Elasticsearch cluster.
 */
class ShardsConfigCheck implements CheckInterface
{
    /**
     * Route to Stores -> Configuration section.
     */
    private const ROUTE_SYSTEM_CONFIG = 'adminhtml/system_config/edit';

    /**
     * Anchor for Stores -> Configuration -> ELASTICSUITE -> Base Settings -> Indices Settings.
     */
    private const ANCHOR_ES_INDICES_SETTINGS_PATH = 'smile_elasticsuite_core_base_settings_indices_settings-link';

    /**
     * URL for Elasticsuite Indices Settings Wiki page.
     */
    private const ES_INDICES_SETTINGS_WIKI_PAGE = 'https://github.com/Smile-SA/elasticsuite/wiki/ModuleInstall#indices-settings';

    public const UNDEFINED_SIZE = 'N/A';

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var IndexStatsProvider
     */
    private $indexStatsProvider;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param IndexSettingsHelper $indexSettingsHelper Index settings helper.
     * @param IndexStatsProvider  $indexStatsProvider  Index stats provider.
     * @param UrlInterface        $urlBuilder          URL builder.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingsHelper,
        IndexStatsProvider $indexStatsProvider,
        UrlInterface $urlBuilder
    ) {
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexStatsProvider  = $indexStatsProvider;
        $this->urlBuilder          = $urlBuilder;
    }

    /**
     * Retrieve the unique identifier for this health check.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'shards_config_check';
    }

    /**
     * Retrieve a dynamic description for this health check based on its status.
     *
     * @return string
     * @throws Exception
     */
    public function getDescription(): string
    {
        $numberOfShards = $this->indexSettingsHelper->getNumberOfShards();
        $maxIndexSize = $this->getMaxIndexSize();
        $status = $this->getStatus();

        if ($status === CheckInterface::WARNING_STATUS) {
            // Description when the shard's configuration is incorrect.
            // @codingStandardsIgnoreStart
            return __(
                'The number of shards configured for Elasticsuite <strong>is incorrect.</strong> '
                . 'You don\'t need to use <strong>%1 shards</strong> since your biggest Elasticsuite index is only <strong>%2</strong>.<br/>'
                . 'Click <a href="%3"><strong>here</strong></a> to go to the <strong>Elasticsuite Basic Settings</strong> page and change your <strong>Number of Shards per Index</strong> parameter according to our <a href="%4" target="_blank"><strong>Wiki page</strong></a>.',
                $numberOfShards,
                $maxIndexSize['human_size'],
                $this->getElasticsuiteConfigUrl(),
                self::ES_INDICES_SETTINGS_WIKI_PAGE
            );
            // @codingStandardsIgnoreEnd
        }

        // Description when the shard's configuration is optimized.
        return __('The number of shards is properly configured for the Elasticsearch cluster. No action is required at this time.');
    }

    /**
     * Retrieve the status of this health check.
     *
     * @return string
     * @throws Exception
     */
    public function getStatus(): string
    {
        $numberOfShards = $this->indexSettingsHelper->getNumberOfShards();
        $maxIndexSize = $this->getMaxIndexSize();

        if ($numberOfShards > 1 && $maxIndexSize && $maxIndexSize['size_in_bytes'] < 10737418240) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Retrieve the sort order for this health check.
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return 20; // Adjust as necessary.
    }

    /**
     * Get size of the largest Elasticsuite index.
     *
     * @return array|false
     * @throws Exception
     */
    private function getMaxIndexSize()
    {
        $elasticsuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();
        $indexSizes = [];

        foreach ($elasticsuiteIndices as $indexName => $indexAlias) {
            $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);

            if (array_key_exists('size', $indexData) && array_key_exists('size_in_bytes', $indexData)
                && $indexData['size_in_bytes'] !== self::UNDEFINED_SIZE) {
                $indexSizes[] = ['human_size' => $indexData['size'], 'size_in_bytes' => $indexData['size_in_bytes']];
            }
        }

        if (!empty($indexSizes)) {
            $indexSizesInBytes = array_column($indexSizes, "size_in_bytes");
            array_multisort($indexSizesInBytes, SORT_DESC, $indexSizes);

            return current($indexSizes);
        }

        return false;
    }

    /**
     * Get URL to the Elasticsuite Configuration page.
     *
     * @return string
     */
    private function getElasticsuiteConfigUrl(): string
    {
        return $this->urlBuilder->getUrl(
            self::ROUTE_SYSTEM_CONFIG,
            ['section' => 'smile_elasticsuite_core_base_settings', '_fragment' => self::ANCHOR_ES_INDICES_SETTINGS_PATH]
        );
    }
}
