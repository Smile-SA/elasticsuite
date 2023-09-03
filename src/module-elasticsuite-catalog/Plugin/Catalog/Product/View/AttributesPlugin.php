<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Edward Crocombe <ecrocombe@outlook.com>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Product\View;

use Smile\ElasticsuiteCatalog\Helper\ProductAttribute;

/**
 * Catalog Product View Attributes plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Edward Crocombe <ecrocombe@outlook.com>
 */
class AttributesPlugin
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $productAttributeHelper;

    /**
     * Constructor.
     *
     * @param ProductAttribute $productAttributeHelper ElasticSuite product attributes helper.
     */
    public function __construct(
        ProductAttribute $productAttributeHelper
    ) {
        $this->productAttributeHelper = $productAttributeHelper;
    }

    /**
     * Add display pattern for frontend display
     *
     * @param \Magento\Catalog\Block\Product\View\Attributes $subject     Plugin Subject
     * @param array                                          $result      Additional data
     * @param string[]                                       $excludeAttr Attribute Codes to exclude
     *
     * @return array                                                      Additional data
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAdditionalData(\Magento\Catalog\Block\Product\View\Attributes $subject, $result, array $excludeAttr = [])
    {
        if (!$this->productAttributeHelper->isFrontendProductDisplayPatternEnabled()) {
            return $result;
        }

        $product = $subject->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            // If attribute is already in array, then isVisibleOnFrontend = `true`.
            if (isset($result[$attribute->getAttributeCode()])) {
                // @codingStandardsIgnoreStart
                $value = isset($result[$attribute->getAttributeCode()]['value'])
                    ? $result[$attribute->getAttributeCode()]['value']
                    : '';
                // @codingStandardsIgnoreEnd

                if (is_numeric($value) && strlen($attribute->getData('display_pattern') ?? '') > 0) {
                    $result[$attribute->getAttributeCode()]['value']
                        = $this->productAttributeHelper->formatProductAttributeValueDisplayPattern($attribute, $value);
                }
            }
        }

        return $result;
    }
}
