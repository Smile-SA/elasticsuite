<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Helper;

use Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * Smile_ElasticSuiteCore search engine client configuration configuration default implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
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
     * {@inheritdoc}
     */
    public function getServerList()
    {
        return explode(',', $this->getElasticsearchClientConfigParam('servers'));
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->getElasticsearchClientConfigParam('enable_debug_mode');
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionTimeout()
    {
        return (int) $this->getElasticsearchClientConfigParam('connection_timeout');
    }

    /**
     * Read config under the path smile_elasticsuite_core_base_settings/es_client.
     *
     * @param string $configField Field name.
     *
     * @return mixed
     */
    private function getElasticsearchClientConfigParam($configField)
    {
        $path = self::ES_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->getElasticSuiteConfigParam($path);
    }
}
