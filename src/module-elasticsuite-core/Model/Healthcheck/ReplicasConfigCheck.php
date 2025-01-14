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

use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Client\Client;

/**
 * Class ReplicasConfigCheck.
 *
 * Health check for replicas misconfiguration in Elasticsuite.
 */
class ReplicasConfigCheck implements CheckInterface
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

    /**
     * @var IndexSettingsHelper
     */
    protected $helper;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param IndexSettingsHelper $indexSettingHelper Index settings helper.
     * @param Client              $client             Elasticsearch client.
     * @param UrlInterface        $urlBuilder         URL builder.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingHelper,
        Client $client,
        UrlInterface $urlBuilder
    ) {
        $this->helper = $indexSettingHelper;
        $this->client = $client;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve the unique identifier for this health check.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'replicas_config_check';
    }

    /**
     * Retrieve a dynamic description for this health check based on its status.
     *
     * @return string
     */
    public function getDescription(): string
    {
        $status = $this->getStatus();

        if ($status === CheckInterface::WARNING_STATUS) {
            // Description when the replicas configuration is incorrect.
            // @codingStandardsIgnoreStart
            return implode(
                '<br />',
                [
                    __(
                'The <strong>number of replicas</strong> configured for Elasticsuite is <strong>incorrect</strong>. You cannot use <strong>%1 replicas</strong> since there is only <strong>%2 nodes</strong> in your Elasticsearch cluster.',
                        $this->helper->getNumberOfReplicas(),
                        $this->getNumberOfNodes()
                    ),
                    __(
                'Click <a href="%1"><strong>here</strong></a> to go to the <strong>Elasticsuite Config</strong> page and change your <strong>Number of Replicas per Index</strong> parameter according to our <a href="%2" target="_blank"><strong>Wiki page</strong></a>.',
                        $this->getElasticsuiteConfigUrl(),
                        self::ES_INDICES_SETTINGS_WIKI_PAGE
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        // Description when the replicas configuration is optimized.
        return __('The number of replicas is properly configured for the Elasticsearch cluster. No action is required at this time.');
    }

    /**
     * Retrieve the status of this health check.
     *
     * @return string
     */
    public function getStatus(): string
    {
        $numberOfReplicas = $this->helper->getNumberOfReplicas();
        $numberOfNodes = $this->getNumberOfNodes();

        if ($numberOfReplicas > 0 && $numberOfReplicas > ($numberOfNodes - 1)) {
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
        return 30; // Adjust as necessary.
    }

    /**
     * Get the number of nodes in the Elasticsearch cluster.
     *
     * @return int
     */
    private function getNumberOfNodes(): int
    {
        $nodeInfo = $this->client->nodes()->info()['_nodes'] ?? [];

        return $nodeInfo['total'] ?? 0;
    }

    /**
     * Get URL to the admin Elasticsuite Configuration page.
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
