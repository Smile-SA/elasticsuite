<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Carey Sizer <carey@balanceinternet.com.au>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface;

/**
 * Client configuration implementation for use during Magento setup.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Carey Sizer <carey@balanceinternet.com.au>
 */
class ClientConfiguration implements ClientConfigurationInterface
{
    /**
     * @var array
     */
    private $serverList;

    /**
     * @var boolean
     */
    private $isDebugModeEnabled;

    /**
     * @var integer
     */
    private $connectionTimeout;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var boolean
     */
    private $isHttpAuthEnabled;

    /**
     * @var string
     */
    private $httpAuthUser;

    /**
     * @var string
     */
    private $httpAuthPassword;

    /**
     * @inheritDoc
     */
    public function __construct(
        $serverList = [],
        $isDebugModeEnabled = false,
        $connectionTimeout = 0,
        $scheme = 'http',
        $isHttpAuthEnabled = false,
        $httpAuthUser = null,
        $httpAuthPassword = null
    ) {
        $this->serverList = $serverList;
        $this->isDebugModeEnabled = $isDebugModeEnabled;
        $this->connectionTimeout = $connectionTimeout;
        $this->scheme = $scheme;
        $this->isHttpAuthEnabled = $isHttpAuthEnabled;
        $this->httpAuthUser = $httpAuthUser;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * @inheritdoc
     */
    public function getServerList()
    {
        return (array) $this->serverList;
    }

    /**
     * @inheritdoc
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->isDebugModeEnabled;
    }

    /**
     * @inheritdoc
     */
    public function getConnectionTimeout()
    {
        return (int) $this->connectionTimeout;
    }

    /**
     * @inheritdoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function isHttpAuthEnabled()
    {
        return (bool) $this->isHttpAuthEnabled;
    }

    /**
     * @inheritdoc
     */
    public function getHttpAuthUser()
    {
        return $this->httpAuthUser;
    }

    /**
     * @inheritdoc
     */
    public function getHttpAuthPassword()
    {
        return $this->httpAuthPassword;
    }
}
