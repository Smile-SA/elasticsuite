<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Model\Healthcheck;

use Exception;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\AbstractCheck;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * Class PrimaryShardsConfigCheck.
 *
 * Checks for primary shards misconfigurations in the Elasticsearch cluster.
 */
class PrimaryShardsConfigCheck extends AbstractCheck
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
     * Constructor.
     *
     * @param IndexSettingsHelper $indexSettingsHelper Index settings helper.
     * @param IndexStatsProvider  $indexStatsProvider  Index stats provider.
     * @param UrlInterface        $urlBuilder          URL builder.
     * @param int                 $sortOrder           Sort order (default: 20).
     * @param int                 $severity            Severity level.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingsHelper,
        IndexStatsProvider $indexStatsProvider,
        UrlInterface $urlBuilder,
        int $sortOrder = 20,
        int $severity = MessageInterface::SEVERITY_MINOR
    ) {
        parent::__construct($urlBuilder, $sortOrder, $severity);
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexStatsProvider  = $indexStatsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'primary_shards_config_check';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $numberOfShards = $this->indexSettingsHelper->getNumberOfShards();
        $maxIndexSize = $this->getMaxIndexSize();
        $status = $this->getStatus();

        if ($status === CheckInterface::STATUS_FAILED) {
            // Description when the shard's configuration is incorrect.
            // @codingStandardsIgnoreStart
            return implode(
                '<br />',
                [
                    __(
                        'The <strong>number of shards</strong> configured for Elasticsuite is <strong>incorrect</strong>.'
                    ),
                    __(
                        'You do not need to use <strong>%1 shards</strong> since your biggest Elasticsuite index is only <strong>%2</strong>.',
                        $numberOfShards,
                        $maxIndexSize['human_size']
                    ),
                    __(
                        'Click <a href="%1"><strong>here</strong></a> to go to the <strong>Elasticsuite Config</strong> page and change your <strong>Number of Shards per Index</strong> parameter according to our <a href="%2" target="_blank"><strong>Wiki page</strong></a>.',
                        $this->getElasticsuiteConfigUrl(),
                        self::ES_INDICES_SETTINGS_WIKI_PAGE
                    )
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        // Description when the shard's configuration is optimized.
        return __('The number of shards is properly configured for the Elasticsearch cluster. No action is required at this time.');
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        $numberOfShards = $this->indexSettingsHelper->getNumberOfShards();
        $maxIndexSize = $this->getMaxIndexSize();

        if ($numberOfShards > 1 && $maxIndexSize && $maxIndexSize['size_in_bytes'] < 10737418240) {
            return CheckInterface::STATUS_FAILED;
        }

        return CheckInterface::STATUS_PASSED;
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
