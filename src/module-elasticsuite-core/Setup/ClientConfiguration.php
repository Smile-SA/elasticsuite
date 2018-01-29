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
    private $isDebugModeEnabled = false;

    /**
     * @var integer
     */
    private $connectionTimeout = 0;

    /**
     * @var string
     */
    private $scheme = null;

    /**
     * @var string
     */
    private $httpAuthUser;

    /**
     * @var string
     */
    private $httpAuthPassword;

    /**
     * ClientConfiguration constructor.
     *
     * @param array $serverList       Server list configuration.
     * @param null  $httpAuthUser     HTTP auth user supplied (optional).
     * @param null  $httpAuthPassword HTTP auth password supplied (optional).
     */
    public function __construct(
        $serverList = [],
        $httpAuthUser = null,
        $httpAuthPassword = null
    ) {
        $this->serverList = $serverList;
        $this->httpAuthUser = $httpAuthUser;
        $this->httpAuthPassword = $httpAuthPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerList()
    {
        return (array) $this->serverList;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool) $this->isDebugModeEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionTimeout()
    {
        return (int) $this->connectionTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function isHttpAuthEnabled()
    {
        $authValues = [$this->getHttpAuthUser(), $this->getHttpAuthPassword()];
        foreach ($authValues as $authValue) {
            if ($authValue !== null && mb_strlen((string) $authValue) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthUser()
    {
        return $this->httpAuthUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpAuthPassword()
    {
        return $this->httpAuthPassword;
    }
}
