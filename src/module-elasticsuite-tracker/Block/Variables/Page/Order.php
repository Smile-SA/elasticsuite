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
namespace Smile\ElasticSuiteTracker\Block\Variables\Page;

use Magento\Framework\View\Element\Template;

/**
 * Order variables block for page tracking, exposes all order related tracking variables
 *
 * @category Smile
 * @package  Smile_ElasticSuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Order extends \Smile\ElasticSuiteTracker\Block\Variables\Page\AbstractBlock
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
     * @param \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper   The Smile Tracker helper
     * @param \Magento\Framework\Registry            $registry        Magento Core Registry
     * @param \Magento\Checkout\Model\Session        $checkoutSession The checkout session
     * @param array                                  $data            The block data
     *
     * @return Order
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper,
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
            $variables['order.id']              = $order->getIncrementId();
            $variables['order.subtotal']        = $order->getBaseSubtotalInclTax();
            $variables['order.discount_total']  = $order->getDiscountAmount();
            $variables['order.shipping_total']  = $order->getShippingAmount();
            $variables['order.grand_total']     = $order->getBaseGrandTotal();
            $variables['order.shipping_method'] = $order->getShippingMethod();
            $variables['order.payment_method']  = $order->getPayment()->getMethod();
            $variables['order.salesrules']      = $order->getAppliedRuleIds();

            foreach ($order->getAllItems() as $item) {
                $variables = array_merge($variables, $this->getOrderItemVariables($item));
            }
        }

        return $variables;
    }

    /**
     * Retrieve tracking variables for an order item
     *
     * @param \Magento\Sales\Model\Order\Item $item The order item
     *
     * @return array
     */
    private function getOrderItemVariables($item)
    {
        $variables = [];

        if (!$item->isDummy()) {
            $itemId = $item->getId();
            $prefix = "order.items.$itemId";
            $variables[$prefix . '.sku']        = $item->getSku();
            $variables[$prefix . '.product_id'] = $item->getProductId();
            $variables[$prefix . '.qty']        = $item->getQtyOrdered();
            $variables[$prefix . '.price']      = $item->getBasePrice();
            $variables[$prefix . '.row_total']  = $item->getRowTotal();
            $variables[$prefix . '.label']      = $item->getName();
            $variables[$prefix . '.salesrules'] = $item->getAppliedRuleIds();

            if ($product = $item->getProduct()) {
                $categoriesId = $product->getCategoryIds();
                if (count($categoriesId)) {
                    $variables[$prefix . '.category_ids'] = implode(",", $categoriesId);
                }
            }
        }

        return $variables;
    }
}
