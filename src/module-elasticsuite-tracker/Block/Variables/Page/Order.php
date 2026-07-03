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
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Framework\View\Element\Template;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

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
     * Product resource model
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                       $context         The template context
     * @param \Magento\Framework\Json\Helper\Data    $jsonHelper      The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper   The Smile Tracker helper
     * @param \Magento\Framework\Registry            $registry        Magento Core Registry
     * @param \Magento\Checkout\Model\Session        $checkoutSession The checkout session
     * @param ProductResource                        $productResource The product resource model
     * @param array                                  $data            The block data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        ProductResource $productResource,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productResource = $productResource;

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

        if ($order && $order->getIncrementId()) {
            $variables['order.subtotal']        = $order->getBaseSubtotalInclTax();
            $variables['order.discount_total']  = $order->getDiscountAmount();
            $variables['order.shipping_total']  = $order->getShippingAmount();
            $variables['order.grand_total']     = $order->getBaseGrandTotal();
            $variables['order.shipping_method'] = $order->getShippingMethod();
            $variables['order.payment_method']  = $order->getPayment()->getMethod();
            $variables['order.salesrules']      = $order->getAppliedRuleIds();

            $itemId = 0;
            $superProductsData = [];
            foreach ($order->getAllItems() as $item) {
                $variables = array_merge($variables, $this->getOrderItemVariables($item, $itemId));
                $itemId++;
                $superProductsData = $this->getSuperProductsData($item, $superProductsData);
            }

            if (!empty($superProductsData)) {
                $variables = array_merge($variables, $this->getSuperOrderItemVariables($superProductsData, $itemId));
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
    private function getOrderItemVariables($item, &$itemId)
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

            $categoriesId = [];
            $product = $item->getProduct();
            if ($product) {
                $categoriesId = $product->getCategoryIds();
                if (count($categoriesId)) {
                    $variables[$prefix . '.category_ids'] = implode(",", $categoriesId);
                }
            }

            if ($item->getProductType() === Configurable::TYPE_CODE) {
                foreach ($item->getChildrenItems() ?? [] as $childItem) {
                    /** @var \Magento\Sales\Model\Order\Item $childItem */
                    $itemId++;
                    $prefix = "order.items.$itemId";
                    $variables[$prefix . '.sku']        = $childItem->getSku();
                    $variables[$prefix . '.product_id'] = $childItem->getProductId();
                    $variables[$prefix . '.qty']        = $childItem->getQtyOrdered();
                    $variables[$prefix . '.price']      = $childItem->getParentItem()->getBasePrice();
                    $variables[$prefix . '.row_total']  = $childItem->getParentItem()->getRowTotal();
                    $variables[$prefix . '.label']      = $childItem->getName();
                    $variables[$prefix . '.salesrules'] = $item->getAppliedRuleIds();
                    if (count($categoriesId)) {
                        $variables[$prefix . '.category_ids'] = implode(",", $categoriesId);
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * Retriever tracker variables for an order item super product (grouped, bundle).
     *
     * @param array $superProductsData Super products.
     * @param int   $itemId            The order item id, dynamically generated.
     *
     * @return array
     */
    private function getSuperOrderItemVariables($superProductsData, &$itemId): array
    {
        $variables = [];

        if (!empty($superProductsData)) {
            $superProductSkus = $this->getSkusByProductIds(array_keys($superProductsData));
            foreach ($superProductsData as $productId => $superProductsDatum) {
                $prefix = "order.items.$itemId";
                if (array_key_exists($productId, $superProductSkus)) {
                    $variables[$prefix. '.sku'] = $superProductSkus[$productId];
                }
                $variables[$prefix . '.product_id'] = $productId;
                $variables[$prefix . '.qty'] = $superProductsDatum['qty'];
                $variables[$prefix . '.row_total']  = $superProductsDatum['row_total'];
                $itemId++;
            }
        }


        return $variables;
    }

    /**
     * Retrieve tracking variables for an order item
     *
     * @param \Magento\Sales\Model\Order\Item $item              The order item.
     * @param array                           $superProductsData Existing super products data.
     *
     * @return array
     */
    private function getSuperProductsData($item, $superProductsData): array
    {
        // Bundle products are present as dummy order items with correct qty and row_total already.
        if (($item->getProductType() === Bundle::TYPE_CODE) && $this->trackerHelper->isTrackingBundleSales()) {
            $bundleProductId = $item->getProductId();
            if ($bundleProductId) {
                if (false === array_key_exists($bundleProductId, $superProductsData)) {
                    $superProductsData[$bundleProductId]['product_id'] = $bundleProductId;
                    $superProductsData[$bundleProductId]['qty'] = 0;
                    $superProductsData[$bundleProductId]['row_total'] = 0;
                }
                $superProductsData[$bundleProductId]['qty'] += $item->getQtyOrdered();
                $superProductsData[$bundleProductId]['row_total'] += $item->getRowTotal();
            }
        }

        // Grouped products are only present through their individual simple products and their options.
        if (($item->getProductType() === Grouped::TYPE_CODE) && $this->trackerHelper->isTrackingGroupedSales()) {
            $groupedProductId = $this->getGroupedProductId($item);
            if ($groupedProductId) {
                if (false === array_key_exists($groupedProductId, $superProductsData)) {
                    $superProductsData[$groupedProductId]['product_id'] = $groupedProductId;
                    $superProductsData[$groupedProductId]['qty'] = 1;
                    $superProductsData[$groupedProductId]['row_total'] = 0;
                }
                $superProductsData[$groupedProductId]['row_total'] += $item->getRowTotal();
            }
        }

        return $superProductsData;
    }

    /**
     * Retrieve the grouped product id from super product config
     *
     * @param \Magento\Sales\Model\Order\Item $item The order item.
     *
     * @return int
     */
    private function getGroupedProductId($item): int
    {
        $groupedProductId = 0;

        $superProductConfig = $item->getProductOptionByCode('super_product_config');
        if (!empty($superProductConfig) && array_key_exists('product_id', $superProductConfig)) {
            $groupedProductId = $superProductConfig['product_id'] ?? 0;
        }

        return $groupedProductId;
    }

    /**
     * Retrieve mapping of product SKUs by product IDs.
     * Used for retrieving the original SKUs of super products.
     *
     * @param array $productIds The super products ids.
     *
     * @return array
     */
    private function getSkusByProductIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return array_column(
            $this->productResource->getProductsSku($productIds),
            'sku',
            'entity_id'
        );
    }
}
