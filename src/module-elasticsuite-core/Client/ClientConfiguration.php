<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Client;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * ElasticSearch client configuration implementation.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientConfiguration implements ClientConfigurationInterface
{
    /**
     * Location of Elasticsearch client configuration.
     *
     * @var string
     */
    const ES_CLIENT_CONFIG_XML_PREFIX = 'smile_elasticsuite_core_base_settings/es_client';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Config.
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerList()
    {
        return explode(',', $this->getElasticsearchClientConfigParam('servers') ?? '');
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
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return (bool) $this->getElasticsearchClientConfigParam('enable_https_mode') ? 'https' : 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpAuthEnabled()
    {
        $authEnabled = (bool) $this->getElasticsearchClientConfigParam('enable_http_auth');

        return $authEnabled && !empty($this->getHttpAuthUser()) && !empty($this->getHttpAuthPassword());
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpAuthEncodingEnabled()
    {
        $authEncodingEnabled = (bool) $this->getElasticsearchClientConfigParam('enable_http_auth_encoding');

        return $authEncodingEnabled && $this->isHttpAuthEnabled() !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthUser()
    {
        return (string) $this->getElasticsearchClientConfigParam('http_auth_user');
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthPassword()
    {
        return (string) $this->getElasticsearchClientConfigParam('http_auth_pwd');
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxParallelHandles()
    {
        return (int) $this->getElasticsearchClientConfigParam('max_parallel_handles');
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxRetries()
    {
        return (int) $this->getElasticsearchClientConfigParam('max_retries');
    }

    /**
     * @return bool
     */
    public function isVerifyEnabled()
    {
        return (bool) $this->getElasticsearchClientConfigParam('enable_certificate_validation');
    }

    /**
     * @return bool
     */
    public function isAwsSig4Enabled()
    {
        return (bool) $this->getElasticsearchClientConfigParam('enable_aws_sig4');
    }

    /**
     * @return string
     */
    public function getAwsService()
    {
        return (string) $this->getElasticsearchClientConfigParam('aws_service');
    }

    /**
     * @return string
     */
    public function getAwsRegion()
    {
        return (string) $this->getElasticsearchClientConfigParam('aws_region');
    }

    /**
     * @return string
     */
    public function getAwsSig4Key()
    {
        return (string) $this->getElasticsearchClientConfigParam('aws_key');
    }

    /**
     * @return string
     */
    public function getAwsSig4Secret()
    {
        return (string) $this->getElasticsearchClientConfigParam('aws_secret');
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        $options = [
            'servers'               => $this->getServerList(),
            'scheme'                => $this->getScheme(),
            'enable_http_auth'      => $this->isHttpAuthEnabled(),
            'http_auth_encoded'     => $this->isHttpAuthEncodingEnabled(),
            'http_auth_user'        => $this->getHttpAuthUser(),
            'http_auth_pwd'         => $this->getHttpAuthPassword(),
            'is_debug_mode_enabled' => $this->isDebugModeEnabled(),
            'max_parallel_handles'  => $this->getMaxParallelHandles(),
            'max_retries'           => $this->getMaxRetries(),
            'verify'                => $this->isVerifyEnabled(),
            'enable_aws_sig4'       => $this->isAwsSig4Enabled(),
            'aws_service'           => $this->getAwsService(),
            'aws_region'            => $this->getAwsRegion(),
            'aws_key'               => $this->getAwsSig4Key(),
            'aws_secret'            => $this->getAwsSig4Secret(),
        ];

        return $options;
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

        return $this->scopeConfig->getValue($path);
    }
}
