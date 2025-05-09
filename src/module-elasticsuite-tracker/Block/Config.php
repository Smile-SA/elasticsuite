<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block;

/**
 * Configuration block for tracker
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * The default tracking consent script, used as a fallback if none defined in layout.
     */
    const DEFAULT_CONSENT_SCRIPT = 'Smile_ElasticsuiteTracker/js/user-consent';

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
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $trackerHelper;

    /**
     * Javascript script that will handle User consent.
     *
     * @var string
     */
    private $userConsentScript;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context       App context
     * @param \Magento\Framework\Json\Helper\Data              $jsonHelper    The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data           $trackerHelper The Smile Tracker helper
     * @param array                                            $data          additional datas
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->scopeConfig       = $context->getScopeConfig();
        $this->jsonHelper        = $jsonHelper;
        $this->trackerHelper     = $trackerHelper;
        $this->userConsentScript = $data['userConsentScript'] ?? self::DEFAULT_CONSENT_SCRIPT;
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
     * Retrieve tracker Rest endpoint URL
     *
     * @return string
     */
    public function getEndpointUrl()
    {
        return $this->trackerHelper->getRestBaseUrl();
    }

    /**
     * Return true if the tracker should use the (Rest) API to push its data to Magento
     *
     * @return bool
     */
    public function isUsingAPI()
    {
        return $this->trackerHelper->isUsingAPI();
    }

    /**
     * Retrieve telemetry Url
     *
     * @return string
     */
    public function getTelemetryUrl()
    {
        return $this->trackerHelper->getTelemetryUrl();
    }

    /**
     * Is telemetry enabled ?
     *
     * @return bool
     */
    public function isTelemetryEnabled()
    {
        return $this->trackerHelper->isTelemetryEnabled();
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

    /**
     * Return the JS script to be used to check if user did consent tracking.
     *
     * @return string
     */
    public function getUserConsentScript()
    {
        return $this->userConsentScript;
    }

    /**
     * Get config passed to the user consent script.
     *
     * @return array
     */
    public function getUserConsentConfig()
    {
        return $this->getData('userConsentConfig') ?? [];
    }
}
