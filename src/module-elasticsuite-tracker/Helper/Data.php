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
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Helper;

use Magento\Framework\App\Helper;

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
    protected $scopeConfig;

    /**
     * Magento Store Manager
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $storeManager;

    /**
     * Magento assets repository
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepository;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context         The current context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager    The Store Manager
     * @param \Magento\Framework\View\Asset\Repository   $assetRepository The asset repository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->storeManager    = $storeManager;
        $this->assetRepository = $assetRepository;
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

            return $this->assetRepository->getUrlWithParams("Smile_ElasticsuiteTracker::hit.png", $params);
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
        return $this->storeManager->getStore()->getId();
    }
}
