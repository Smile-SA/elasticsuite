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
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Framework\View\Element\Template;

/**
 * Order variables block for page tracking, exposes all order related tracking variables
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Order extends \Smile\ElasticsuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                       $context         The template context
     * @param \Magento\Framework\Json\Helper\Data    $jsonHelper      The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper   The Smile Tracker helper
     * @param \Magento\Framework\Registry            $registry        Magento Core Registry
     * @param \Magento\Checkout\Model\Session        $checkoutSession The checkout session
     * @param array                                  $data            The block data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Return order and it's item related variables
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = [];

        $order = $order = $this->checkoutSession->getLastRealOrder();

        if ($order) {
            $variables['order.subtotal']        = $order->getBaseSubtotalInclTax();
            $variables['order.discount_total']  = $order->getDiscountAmount();
            $variables['order.shipping_total']  = $order->getShippingAmount();
            $variables['order.grand_total']     = $order->getBaseGrandTotal();
            $variables['order.shipping_method'] = $order->getShippingMethod();
            $variables['order.payment_method']  = $order->getPayment()->getMethod();
            $variables['order.salesrules']      = $order->getAppliedRuleIds();

            $itemId = 0;
            foreach ($order->getAllItems() as $item) {
                $variables = array_merge($variables, $this->getOrderItemVariables($item, $itemId++));
            }
        }

        return $variables;
    }

    /**
     * Retrieve tracking variables for an order item
     *
     * @param \Magento\Sales\Model\Order\Item $item   The order item
     * @param int                             $itemId The order item id, dynamically generated.
     *
     * @return array
     */
    private function getOrderItemVariables($item, $itemId)
    {
        $variables = [];

        if (!$item->isDummy()) {
            $prefix = "order.items.$itemId";
            $variables[$prefix . '.sku']        = $item->getSku();
            $variables[$prefix . '.product_id'] = $item->getProductId();
            $variables[$prefix . '.qty']        = $item->getQtyOrdered();
            $variables[$prefix . '.price']      = $item->getBasePrice();
            $variables[$prefix . '.row_total']  = $item->getRowTotal();
            $variables[$prefix . '.label']      = $item->getName();
            $variables[$prefix . '.salesrules'] = $item->getAppliedRuleIds();

            $product = $item->getProduct();
            if ($product) {
                $categoriesId = $product->getCategoryIds();
                if (count($categoriesId)) {
                    $variables[$prefix . '.category_ids'] = implode(",", $categoriesId);
                }
            }
        }

        return $variables;
    }
}
