<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Plugin;

use \Magento\Quote\Model\Quote;
use \Magento\Catalog\Model\Product;
use \Magento\Catalog\Model\Product\Type\AbstractType;

/**
 * Log add to cart events into the event queue.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QuotePlugin
{
    /**
     * @var \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface
     */
    private $service;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $trackerHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $service       Tracker service.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface                $cookieManager Cookie manager.
     * @param \Smile\ElasticsuiteTracker\Helper\Data                          $trackerHelper Tracker helper.
     * @param \Psr\Log\LoggerInterface                                        $logger        Logger.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $service,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->service       = $service;
        $this->cookieManager = $cookieManager;
        $this->trackerHelper = $trackerHelper;
        $this->logger        = $logger;
    }

    /**
     * Log add to cart events into the event queue.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Quote                           $subject Quote.
     * @param \Magento\Quote\Model\Quote\Item $result  Quote Item.
     *
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function afterAddProduct(
        Quote $subject,
        $result
    ) {
        try {
            if ($result instanceof \Magento\Quote\Model\Quote\Item) {
                /** @var \Magento\Quote\Model\Quote\Item $result */
                $product = $result->getProduct();
                if ($product !== null) {
                    $this->logEvent($product->getId(), $product->getStoreId());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), []);
        }

        return $result;
    }


    /**
     * Log the event.
     *
     * @param int $productId Product Id
     * @param int $storeId   Store Id
     *
     * @return void
     */
    private function logEvent(int $productId, int $storeId): void
    {
        $pageData = [];
        $pageData['store_id']           = $storeId;
        $pageData['cart']['product_id'] = $productId;

        $eventData = ['page' => $pageData, 'session' => $this->getSessionData()];

        $this->service->addEvent($eventData);
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

        return $sessionData;
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
