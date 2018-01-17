<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * Smile_ElasticsuiteCore search engine client configuration configuration default implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientConfiguration extends AbstractHelper implements ClientConfigurationInterface
{
    /**
     * Location of Elasticsearch client configuration.
     *
     * @deprecated
     * @var string
     */
    const ES_CLIENT_CONFIG_XML_PREFIX = 'es_client';

    /**
     * Configuration path for client servers list.
     *
     * @var string
     */
    const CONFIG_PATH_CLIENT_SERVERS = 'smile_elasticsuite_core_base_settings/es_client/servers';

    /**
     * Configuration path for debug mode enabled.
     *
     * @var string
     */
    const CONFIG_PATH_DEBUG_MODE_ENABLED = 'smile_elasticsuite_core_base_settings/es_client/enable_debug_mode';

    /**
     * Configuration path for connection timeout.
     *
     * @var string
     */
    const CONFIG_PATH_CONNECTION_TIMEOUT = 'smile_elasticsuite_core_base_settings/es_client/connection_timeout';

    /**
     * Configuration path for HTTPS mode.
     *
     * @var string
     */
    const CONFIG_PATH_ENABLE_HTTPS_MODE = 'smile_elasticsuite_core_base_settings/es_client/enable_https_mode';

    /**
     * Configuration path for enable HTTP auth.
     *
     * @var string
     */
    const CONFIG_PATH_ENABLE_HTTP_AUTH = 'smile_elasticsuite_core_base_settings/es_client/enable_http_auth';

    /**
     * Configuration path for HTTP auth user.
     *
     * @var string
     */
    const CONFIG_PATH_HTTP_AUTH_USER = 'smile_elasticsuite_core_base_settings/es_client/http_auth_user';

    /**
     * Configuration path for HTTP auth user.
     *
     * @var string
     */
    const CONFIG_PATH_HTTP_AUTH_PWD = 'smile_elasticsuite_core_base_settings/es_client/http_auth_pwd';

    /**
     * {@inheritdoc}
     */
    public function getServerList()
    {
        return explode(',', $this->scopeConfig->getValue(self::CONFIG_PATH_CLIENT_SERVERS));
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_DEBUG_MODE_ENABLED);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionTimeout()
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_PATH_CONNECTION_TIMEOUT);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_ENABLE_HTTPS_MODE) ? 'https' : 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpAuthEnabled()
    {
        $authEnabled = (bool) $this->scopeConfig->getValue(self::CONFIG_PATH_ENABLE_HTTP_AUTH);

        return $authEnabled && !empty($this->getHttpAuthUser()) && !empty($this->getHttpAuthPassword());
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthUser()
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_HTTP_AUTH_USER);
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthPassword()
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_HTTP_AUTH_PWD);
    }
}
