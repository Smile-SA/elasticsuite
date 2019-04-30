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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Observer;

/**
 * Customer Logout observer : used to clean tracking cookies on customer log out.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CleanCookiesOnLogout implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * CleanCookiesOnLogout constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Helper\Data                 $helper                Elasticsuite tracking helper.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface       $cookieManager         Cookie Manager.
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory Cookie Metadata Factory.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Helper\Data $helper,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->helper                = $helper;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * Clean customer tracking cookies when logging out.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @event customer_logout
     *
     * @param \Magento\Framework\Event\Observer $observer The observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cookieNames = $this->getCookies();

        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $metadata->setPath($this->getCookieConfiguration()['path'] ?? '/');

        foreach ($cookieNames as $cookieName) {
            $this->cookieManager->deleteCookie($cookieName, $metadata);
        }
    }

    /**
     * Retrieve cookies names from configuration.
     *
     * @return array
     */
    private function getCookies()
    {
        $cookieConfiguration = $this->getCookieConfiguration();

        $cookies = [
            $cookieConfiguration['visit_cookie_name'] ?? null,
            $cookieConfiguration['visitor_cookie_name'] ?? null,
        ];

        return array_filter($cookies);
    }

    /**
     * Retrieve Cookie configuration.
     *
     * @return array
     */
    private function getCookieConfiguration()
    {
        return $this->helper->getCookieConfig();
    }
}
