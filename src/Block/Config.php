<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteTracker\Block;

/**
 * Configuration block for tracker
 *
 * @category Smile
 * @package  Smile_ElasticSuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * Magento Configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * Generic tracker helper
     *
     * @var \Smile\ElasticSuiteTracker\Helper\Data
     */
    private $trackerHelper;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context   $context       App context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig   The Magento configuration
     * @param \Magento\Framework\Json\Helper\Data                $jsonHelper    The Magento's JSON Helper
     * @param \Smile\ElasticSuiteTracker\Helper\Data             $trackerHelper The Smile Tracker helper
     * @param array                                              $data          additional datas
     *
     * @return Config
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->scopeConfig   = $scopeConfig;
        $this->jsonHelper    = $jsonHelper;
        $this->trackerHelper = $trackerHelper;
    }

    /**
     * Check that the module is currently enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->trackerHelper->isEnabled();
    }

    /**
     * Retrieve the Json Helper
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }

    /**
     * Return the session cookie configuration (names and lifetimes)
     * for cookies used by the tracker (visit/session and visitor).
     *
     * @return array
     */
    public function getCookieConfig()
    {
        $config = $this->trackerHelper->getCookieConfig();

        return $config;
    }

    /**
     * Retrieve beacon Url
     *
     * @return string
     */
    public function getBeaconUrl()
    {
        return $this->trackerHelper->getBaseUrl();
    }

    /**
     * Return the tracked store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->trackerHelper->getStoreId();
    }
}
