<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Observer;

use Magento\CatalogSearch\Helper\Data;
use Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;
use Magento\Framework\View\Layout\PageType\Config as PageTypeConfig;
use Smile\ElasticsuiteTracker\Model\CustomerDataTrackingManager;

/**
 * Logs a search event when the Smile\ElasticsuiteCatalog plugin redirects to a product page when only one result is found.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class TrackRedirectIfOneResult implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    private $helper;

    /**
     * @var CustomerTrackingServiceInterface
     */
    private $service;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var TrackerHelper
     */
    private $trackerHelper;

    /**
     * @var PageTypeConfig
     */
    private $pageTypeConfig;

    /**
     * @var \Smile\ElasticsuiteTracker\Model\CustomerDataTrackingManager
     */
    private $customerData;

    /**
     * TrackRedirectIfOneResult constructor.
     *
     * @param \Magento\CatalogSearch\Helper\Data                              $catalogSearchHelper Catalog Search Helper
     * @param \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $service             Customer Tracking Service
     * @param \Magento\Framework\Stdlib\CookieManagerInterface                $cookieManager       Cookie Manager
     * @param \Smile\ElasticsuiteTracker\Helper\Data                          $trackerHelper       Tracker Helper
     * @param \Magento\Framework\View\Layout\PageType\Config                  $pageTypeConfig      The Page Type Configuration
     * @param \Smile\ElasticsuiteTracker\Model\CustomerDataTrackingManager    $customerData        Customer Data for tracking.
     */
    public function __construct(
        Data $catalogSearchHelper,
        CustomerTrackingServiceInterface $service,
        CookieManagerInterface $cookieManager,
        TrackerHelper $trackerHelper,
        PageTypeConfig $pageTypeConfig,
        CustomerDataTrackingManager $customerData
    ) {
        $this->helper         = $catalogSearchHelper;
        $this->service        = $service;
        $this->cookieManager  = $cookieManager;
        $this->trackerHelper  = $trackerHelper;
        $this->pageTypeConfig = $pageTypeConfig;
        $this->customerData   = $customerData;
    }

    /**
     * Logs a fulltext search event before the user is redirected to the product page.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @event elasticsuite_redirect_if_one_result
     *
     * @param \Magento\Framework\Event\Observer $observer The observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productCollection = $observer->getEvent()->getData('product_collection');
        $storeId           = $observer->getEvent()->getData('store_id');

        if (($productCollection instanceof \Magento\Catalog\Model\ResourceModel\Product\Collection) && $storeId) {
            $this->logEvent($productCollection, $storeId);
        }
    }

    /**
     * Log the event.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection Product collection.
     * @param int                                                     $storeId           Store Id.
     *
     * @return void
     */
    private function logEvent($productCollection, int $storeId): void
    {
        $sessionData = $this->getSessionData();

        if (!empty($sessionData)) {
            $pageData = [
                'store_id'     => $storeId,
                'type'         => $this->getPageTypeInformations(),
                'product_list' => [
                    'page_count'    => 1,
                    'current_page'  => 1,
                    'product_count' => 1,
                ],
                'search'       => [
                    'query'             => $this->helper->getEscapedQueryText(),
                    'is_spellchecked'   => (int) $productCollection->isSpellchecked(),
                ],
            ];

            $eventData = ['page' => $pageData, 'session' => $sessionData, 'customer' => $this->customerData->getCustomerDataToTrack()];

            $this->service->addEvent($eventData);
        }
    }

    /**
     * List of the page type data
     *
     * @return array
     */
    private function getPageTypeInformations()
    {
        return [
            'identifier' => 'catalogsearch_result_index',
            'label'      => stripslashes($this->getPageTypeLabel('catalogsearch_result_index')),
        ];
    }

    /**
     * Human readable version of the page type identifier.
     *
     * @param string $pageTypeIdentifier Page type identifier.
     *
     * @return string
     */
    private function getPageTypeLabel($pageTypeIdentifier)
    {
        $label             = '';
        $labelByIdentifier = $this->getPageTypeLabelMap();

        if (isset($labelByIdentifier[$pageTypeIdentifier])) {
            $label = $labelByIdentifier[$pageTypeIdentifier];
        }

        return $label;
    }

    /**
     * Return the array of page labels from layout indexed by handle names.
     *
     * @return array
     */
    private function getPageTypeLabelMap()
    {
        $labelByIdentifier = [];

        $pageTypes = $this->pageTypeConfig->getPageTypes();
        foreach ($pageTypes as $identifier => $pageType) {
            $labelByIdentifier[$identifier] = $pageType['label'];
        }

        return $labelByIdentifier;
    }


    /**
     * Read session data.
     *
     * @return string[]
     */
    private function getSessionData()
    {
        $cookieConfig = $this->trackerHelper->getCookieConfig();

        $sessionData = [
            'uid' => $this->readCookieValue($cookieConfig['visit_cookie_name']),
            'vid' => $this->readCookieValue($cookieConfig['visitor_cookie_name']),
        ];

        return array_filter($sessionData);
    }

    /**
     * Read cookie value.
     *
     * @param string $cookieName Cookie name.
     *
     * @return string|NULL
     */
    private function readCookieValue($cookieName)
    {
        return $this->cookieManager->getCookie($cookieName);
    }
}
