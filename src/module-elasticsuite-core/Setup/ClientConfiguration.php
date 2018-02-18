<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Carey Sizer <carey@balanceinternet.com.au>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * ElasticSearch client configuration implementation.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Carey Sizer <carey@balanceinternet.com.au>
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
     * @var array
     */
    private $options;

    /**
     * @param array $options Custom options.
     */
    public function __construct($options = [])
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerList()
    {
        return explode(',', ($this->options['servers'] ?? null));
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool) ($this->options['enable_debug_mode'] ?? false);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionTimeout()
    {
        return (int) ($this->options['connection_timeout'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return (bool) ($this->options['enable_https_mode'] ?? false) ? 'https' : 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpAuthEnabled()
    {
        $authEnabled = (bool) ($this->options['enable_http_auth'] ?? false);

        return $authEnabled && !empty($this->getHttpAuthUser()) && !empty($this->getHttpAuthPassword());
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthUser()
    {
        return (string) ($this->options['http_auth_user'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthPassword()
    {
        return (string) ($this->options['http_auth_pwd'] ?? null);
    }
}
