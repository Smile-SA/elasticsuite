<?php
/**
 * _______________________________
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile________________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Apache License Version 2.0
 */
namespace Smile\ElasticSuiteTracker\Helper;
use Magento\Framework\App\Helper;

/**
 * Smile Tracker helper
 *
 * @package   Smile\ElasticSuiteTracker\Helper\
 * @copyright 2016 Smile
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Module status configuration path
     * @var string
     */
    const CONFIG_IS_ENABLED_XPATH = 'smile_elasticsuite_tracker/general/enabled';

    /**
     * Tracking URL configuration path
     * @var string
     */
    const CONFIG_BASE_URL_XPATH   = 'smile_elasticsuite_tracker/general/base_url';

    /**
     * Coookie configuration configuration path
     * @var string
     */
    const CONFIG_COOKIE           = 'smile_elasticsuite_tracker/session';

    /**
     * Magento Configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Magento Store Manager
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_storeManager;

    /**
     * Magento assets repository
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context         The current context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager    The Store Manager
     * @param \Magento\Framework\View\Asset\Repository   $assetRepository The asset repository
     *
     * @return Data
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->_storeManager    = $storeManager;
        $this->_assetRepository = $assetRepository;
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
        $result = $this->scopeConfig->getValue(self::CONFIG_BASE_URL_XPATH);

        if (!$result) {

            $params = ['_secure' => $this->_getRequest()->isSecure()];
            return $this->_assetRepository->getUrlWithParams("Smile_ElasticSuiteTracker::hit.png", $params);
        }

        return $result;
    }

    /**
     * Return an array containing the cookie configuration
     *
     * @return array
     */
    public function getCookieConfig()
    {
        return $this->scopeConfig->getValue(self::CONFIG_COOKIE);
    }

    /**
     * Retrieve current store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
}