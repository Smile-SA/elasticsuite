<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\System\Message;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Client\Client;

/**
 * ElasticSuite Warning about Cluster mis-configuration for replicas
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class WarningAboutClusterReplicasMisconfig implements MessageInterface
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
     * @var Client
     */
    protected $client;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param IndexSettingsHelper $indexSettingHelper Index settings helper
     * @param Client              $client             ElasticSearch client
     * @param UrlInterface        $urlBuilder         Url builder
     */
    public function __construct(
        IndexSettingsHelper $indexSettingHelper,
        Client $client,
        UrlInterface $urlBuilder
    ) {
        $this->helper              = $indexSettingHelper;
        $this->client              = $client;
        $this->urlBuilder          = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        if ($this->helper->getNumberOfReplicas() > 1) {
            /* numberOfReplicas should be <= numberOfNodes - 1 */
            if ($this->helper->getNumberOfReplicas() <= $this->getNumberOfNodes() - 1) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return hash('sha256', 'ELASTICSUITE_REPLICAS_WARNING');
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
        $messageDetails .= __(
            'The number of replicas configured for Elasticsuite is incorrect. You cannot use <strong>%1 replicas</strong> since there is only <strong>%2 nodes</strong> in your Elasticsearch cluster.',
            $this->helper->getNumberOfReplicas(),
            $this->getNumberOfNodes()
        ) . '<br/>';
        $messageDetails .= __(
            'Click here to go to the <a href="%1"><strong>Elasticsuite Config</strong></a> page and change your <strong>Number of Replicas per Index</strong> parameter according to our <a href="%2" target="_blank"><strong>wiki page</strong></a>.',
            $this->getElasticsuiteConfigUrl(),
            self::ES_INDICES_SETTINGS_WIKI_PAGE
        );
        // @codingStandardsIgnoreEnd

        return $messageDetails;
    }

    /**
     * Get number of nodes from ElasticSearch client
     *
     * @return int
     */
    public function getNumberOfNodes()
    {
        if (is_array($this->client->nodes()->info()['_nodes'])
            && array_key_exists('total', $this->client->nodes()->info()['_nodes'])) {
                return (int) $this->client->nodes()->info()['_nodes']['total'];
        }

        return 0;
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
