<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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
     * @var \Smile\ElasticsuiteTracker\Api\EventQueueInterface
     */
    private $eventQueue;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $trackerHelper;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue    Tracker event queue.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface   $cookieManager Cookie manager.
     * @param \Smile\ElasticsuiteTracker\Helper\Data             $trackerHelper Tracker helper.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper
    ) {
        $this->eventQueue    = $eventQueue;
        $this->cookieManager = $cookieManager;
        $this->trackerHelper = $trackerHelper;
    }

    /**
     * Log add to cart events into the event queue.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Quote                                    $subject     Quote.
     * @param callable                                 $proceed     Original method.
     * @param Product                                  $product     Product added.
     * @param null|float|\Magento\Framework\DataObject $request     Add to cart request.
     * @param null|string                              $processMode Process Mode.
     *
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function aroundAddProduct(
        Quote $subject,
        callable $proceed,
        Product $product,
        $request = null,
        $processMode = AbstractType::PROCESS_MODE_FULL
    ) {
        $returnValue = $proceed($product, $request, $processMode);

        $this->logEvent($product);

        return $returnValue;
    }

    /**
     * Log the event.
     *
     * @param Product $product Product added.
     *
     * @return void
     */
    private function logEvent(Product $product)
    {
        $pageData = [];
        $pageData['store_id']           = $product->getStoreId();
        $pageData['cart']['product_id'] = $product->getId();

        $eventData = ['page' => $pageData, 'session' => $this->getSessionData()];

        $this->eventQueue->addEvent($eventData);
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
