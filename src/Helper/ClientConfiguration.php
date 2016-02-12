<?php
/**
 * Smile_ElasticSuiteCore search engine configuration default implementation.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * Smile_ElasticSuiteCore search engine client configuration configuration default implementation.
 */
class ClientConfiguration extends AbstractConfiguration implements ClientConfigurationInterface
{
    /**
     * Location of ElasticSearch client configuration.
     *
     * @var string
     */
    const ES_CLIENT_CONFIG_XML_PREFIX = 'es_client';

    /**
     * Read a configuration param under the SEARCH_CONFIG_XML_PREFIX ('catalog/search/elasticsearch_').
     *
     * @param string $configField
     *
     * @return mixed
     */
    private function getElasticsearchClientConfigParam($configField)
    {
        $path = self::ES_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->getElasticSuiteConfigParam($path);
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface::getServerList()
     */
    function getServerList()
    {
        return explode(',', $this->getElasticsearchClientConfigParam('servers'));
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface::isDebugModeEnabled()
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->getElasticsearchClientConfigParam('enable_debug_mode');
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface::getConnectionTimeout()
     */
    public function getConnectionTimeout()
    {
        return (int) $this->getElasticsearchClientConfigParam('connection_timeout');
    }
}
