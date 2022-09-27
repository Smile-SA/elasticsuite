<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * ElasticSuite Warning about Cluster mis-configuration for shards
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class WarningAboutClusterShardsMisconfig implements MessageInterface
{
    /**
     * Route to Stores -> Configuration section
     */
    private const ROUTE_SYSTEM_CONFIG = 'adminhtml/system_config/edit';

    /**
     * Anchor for Stores -> Configuration -> ELASTICSUITE -> Base Settings -> Indices Settings
     */
    private const ANCHOR_ES_INDICES_SETTINGS_PATH = 'smile_elasticsuite_core_base_settings_indices_settings-link';

    /**
     * URL for Elasticsuite Indices Settings wiki page
     */
    private const ES_INDICES_SETTINGS_WIKI_PAGE = 'https://github.com/Smile-SA/elasticsuite/wiki/ModuleInstall#indices-settings';

    /**
     * @var IndexSettingsHelper
     */
    protected $helper;

    /**
     * @var IndexStatsProvider
     */
    protected $indexStatsProvider;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param IndexSettingsHelper $indexSettingHelper Index settings helper
     * @param IndexStatsProvider  $indexStatsProvider Index stats provider
     * @param UrlInterface        $urlBuilder         Url builder
     */
    public function __construct(
        IndexSettingsHelper    $indexSettingHelper,
        IndexStatsProvider $indexStatsProvider,
        UrlInterface $urlBuilder
    ) {
        $this->helper             = $indexSettingHelper;
        $this->indexStatsProvider = $indexStatsProvider;
        $this->urlBuilder         = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        $numberOfShards = $this->helper->getNumberOfShards();
        $indexMaxSize = $this->getElasticsuiteIndexMaxSize()['size_in_bytes'];

        if ($numberOfShards > 1) {
            /* 10 Gb => 10737418240 bytes (in binary) */
            if ($indexMaxSize < '10737418240') {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return hash('sha256', 'ELASTICSUITE_SHARDS_WARNING');
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        $messageDetails = '';

        // @codingStandardsIgnoreStart
        $messageDetails .= __('The number of shards configured for Elasticsuite is incorrect.') . ' ';
        $messageDetails .= __(
            'You do not need to use <strong>%1 shards</strong> since your biggest Elasticsuite index is only <strong>%2</strong>.',
            $this->helper->getNumberOfShards(),
            $this->getElasticsuiteIndexMaxSize()['human_size']
        ) . '<br/>';
        $messageDetails .= __(
            'Click here to go to the <a href="%1"><strong>Elasticsuite Config</strong></a> page and change your <strong>Number of Shards per Index</strong> parameter according to our <a href="%2" target="_blank"><strong>wiki page</strong></a>.',
            $this->getElasticsuiteConfigUrl(),
            self::ES_INDICES_SETTINGS_WIKI_PAGE
        );
        // @codingStandardsIgnoreEnd

        return $messageDetails;
    }

    /**
     * Get size of the biggest Elasticsuite Indices
     *
     * @return mixed
     * @throws \Exception
     */
    private function getElasticsuiteIndexMaxSize()
    {
        $elasticsuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();
        $indexSizes = [];

        foreach ($elasticsuiteIndices as $indexName => $indexAlias) {
            $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);
            $indexSizes[] = ['human_size' => $indexData['size'], 'size_in_bytes' => $indexData['size_in_bytes']];
        }

        $indexSizesInBytes = array_column($indexSizes, "size_in_bytes");
        array_multisort($indexSizesInBytes, SORT_DESC, $indexSizes);

        return current($indexSizes);
    }

    /**
     * Get URL to the admin Elasticsuite Configuration page
     *
     * @return string
     */
    private function getElasticsuiteConfigUrl()
    {
        return $this->urlBuilder->getUrl(
            self::ROUTE_SYSTEM_CONFIG,
            ['section' => 'smile_elasticsuite_core_base_settings', '_fragment' => self::ANCHOR_ES_INDICES_SETTINGS_PATH]
        );
    }
}
