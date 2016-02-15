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
namespace Smile\ElasticSuiteTracker\Block;

/**
 * Class Config
 *
 * @package   Smile\ElasticSuiteTracker\Block\
 * @copyright 2016 Smile
 */
class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * Magento Configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * Generic tracker helper
     *
     * @var \Smile\Tracker\Helper\Data
     */
    protected $_trackerHelper;

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

        $this->_scopeConfig   = $scopeConfig;
        $this->_jsonHelper    = $jsonHelper;
        $this->_trackerHelper = $trackerHelper;
    }

    /**
     * Check that the module is currently enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_trackerHelper->isEnabled();
    }

    /**
     * Retrieve beacon Url
     *
     * @return string
     */
    public function getBeaconUrl()
    {
        return $this->_trackerHelper->getBaseUrl();
    }

    /**
     * Return the tracked site id.
     *
     * @return string
     */
    public function getSiteId()
    {
        return 1;
    }

    /**
     * Return the tracked store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_trackerHelper->getStoreId();
    }

    /**
     * Return the session cookie configuration (names and lifetimes)
     * for cookies used by the tracker (visit/session and visitor).
     *
     * @return array
     */
    public function getCookieConfig()
    {
        $config = $this->_trackerHelper->getCookieConfig();
        return $config;
    }

    /**
     * Retrieve the Json Helper
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->_jsonHelper;
    }

    /**
     * Retrieve the configuration reader
     *
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->_scopeConfig;
    }

    /**
     * Retrieve the string escaper
     *
     * @return \Magento\Framework\Escaper
     */
    public function getEscaper()
    {
        return $this->_escaper;
    }
}