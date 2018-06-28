<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Helper;

/**
 * Smile Tracker helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Module status configuration path
     * @var string
     */
    const CONFIG_IS_ENABLED_XPATH = 'smile_elasticsuite_tracker/general/enabled';

    /**
     * Coookie configuration configuration path
     * @var string
     */
    const CONFIG_COOKIE           = 'smile_elasticsuite_tracker/session';

    /**
     * Anonymization status configuration path
     * @var string
     */
    const CONFIG_IS_ANONYMIZATION_ENABLED_XPATH = 'smile_elasticsuite_tracker/anonymization/enabled';

    /**
     * Anonymization delay configuration path
     * @var string
     */
    const CONFIG_ANONYMIZATION_DELAY_XPATH      = 'smile_elasticsuite_tracker/anonymization/delay';

    /**
     * Magento Configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Magento Store Manager
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $sessionManager;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\App\Helper\Context              $context        The current context
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager   The Store Manager
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager Session Manager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        $this->urlBuilder     = $context->getUrlBuilder();
        $this->storeManager   = $storeManager;
        $this->sessionManager = $sessionManager;
        parent::__construct($context);
    }

    /**
     * Return the module activation status
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->scopeConfig->getValue(self::CONFIG_IS_ENABLED_XPATH);
    }

    /**
     * Return the tracking base URL (params are added later)
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return trim($this->urlBuilder->getUrl('elasticsuite/tracker/hit', ['image' => 'h.png']), '/');
    }

    /**
     * Return an array containing the cookie configuration
     *
     * @return array
     */
    public function getCookieConfig()
    {
        $config           = $this->scopeConfig->getValue(self::CONFIG_COOKIE);
        $config['domain'] = $this->sessionManager->getCookieDomain();
        $config['path']   = $this->sessionManager->getCookiePath();

        return $config;
    }

    /**
     * Retrieve current store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }


    /**
     * Check if Anonymization is enabled.
     *
     * @return bool
     */
    public function isAnonymizationEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_IS_ANONYMIZATION_ENABLED_XPATH);
    }

    /**
     * Retrieve anonymization delay (in days).
     *
     * @return int
     */
    public function getAnonymizationDelay()
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_ANONYMIZATION_DELAY_XPATH);
    }
}
