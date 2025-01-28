<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Observer;

use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;
use Magento\Framework\View\Layout\PageType\Config as PageTypeConfig;

/**
 * Logs a search event when the Smile\ElasticsuiteCatalog plugin redirects to a target Redirect URL.
 *
 * Listens to the `smile_elasticsuite_redirect_search_term` event and logs the redirect data for tracking purposes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class TrackRedirectIfSearchTerm implements ObserverInterface
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
     * Constructor.
     *
     * @param \Magento\CatalogSearch\Helper\Data                              $catalogSearchHelper Catalog Search Helper.
     * @param \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $service             Customer Tracking Service.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface                $cookieManager       Cookie Manager.
     * @param \Smile\ElasticsuiteTracker\Helper\Data                          $trackerHelper       Tracker Helper.
     * @param \Magento\Framework\View\Layout\PageType\Config                  $pageTypeConfig      The Page Type Configuration.
     */
    public function __construct(
        Data $catalogSearchHelper,
        CustomerTrackingServiceInterface $service,
        CookieManagerInterface $cookieManager,
        TrackerHelper $trackerHelper,
        PageTypeConfig $pageTypeConfig
    ) {
        $this->helper         = $catalogSearchHelper;
        $this->service        = $service;
        $this->cookieManager  = $cookieManager;
        $this->trackerHelper  = $trackerHelper;
        $this->pageTypeConfig = $pageTypeConfig;
    }

    /**
     * Logs a fulltext search event before the user is redirected to a Redirect URL.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @event smile_elasticsuite_redirect_if_search_term
     *
     * @param \Magento\Framework\Event\Observer $observer The observer.
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productCollection = $observer->getEvent()->getData('product_collection');
        $storeId           = $observer->getEvent()->getData('store_id');
        $redirectUrl       = $observer->getEvent()->getData('redirect_url');

        if (($productCollection instanceof \Magento\Catalog\Model\ResourceModel\Product\Collection) && $storeId) {
            $this->logEvent($productCollection, $storeId, $redirectUrl);
        }

        $this->logEvent($productCollection, $storeId, $redirectUrl);
    }

    /**
     * Log the event.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection Product collection.
     * @param int                                                     $storeId           Store ID.
     * @param string                                                  $redirectUrl       Redirect URL.
     *
     * @return void
     */
    private function logEvent($productCollection, int $storeId, string $redirectUrl): void
    {
        $sessionData = $this->getSessionData();

        if (!empty($sessionData)) {
            $pageData = [
                'store_id'  => $storeId,
                'search'    => [
                    'query'             => $this->helper->getEscapedQueryText(),
                    'redirect_url'      => $redirectUrl,
                    'is_spellchecked'   => (int) $productCollection->isSpellchecked(),
                ],
                'product_list' => [
                    'page_count'    => $productCollection->getLastPageNumber(),
                    'current_page'  => $productCollection->getCurPage(),
                    'product_count' => $productCollection->getSize(),
                ],
                'type' => $this->getPageTypeInformations(),
            ];

            $eventData = ['page' => $pageData, 'session' => $sessionData];

            $this->service->addEvent($eventData);
        }
    }

    /**
     * List of the page type data.
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
