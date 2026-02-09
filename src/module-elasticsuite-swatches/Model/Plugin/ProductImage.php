<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Model\Plugin;

use Magento\Eav\Model\Entity\Attribute;
use Magento\Catalog\Model\Product;

/**
 * Plugin that allow to select the right product image when a filter is selected.
 * Please note: the custom behavior is restricted to getFilterArray, the other methods (loadSimpleVariation, canReplaceImageWithSwatch)
 * had to be redefined for visibility reasons.
 *
 * @category   Smile
 * @package    Smile\ElasticsuiteSwatches
 * @author     Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @deprecated since Magento 2.1.6
 */
class ProductImage extends \Magento\Swatches\Model\Plugin\ProductImage
{
    /**
     * {@inheritdoc}
     */
    public function beforeGetImage(
        \Magento\Catalog\Block\Product\AbstractProduct $subject,
        \Magento\Catalog\Model\Product $product,
        $location,
        array $attributes = []
    ) {
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
            && ($location == self::CATEGORY_PAGE_GRID_LOCATION || $location == self::CATEGORY_PAGE_LIST_LOCATION)) {
            $request = $this->request->getParams();
            if (is_array($request)) {
                $filterArray = $this->getFilterArray($request, $product);
                if (!empty($filterArray)) {
                    $product = $this->loadSimpleVariation($product, $filterArray);
                }
            }
        }

        return [$product, $location, $attributes];
    }

    /**
     * Get filters from request and replace labels by option ids.
     *
     * @param array                          $request Request parameters.
     * @param \Magento\Catalog\Model\Product $product Product.
     *
     * @return array
     */
    private function getFilterArray(array $request, \Magento\Catalog\Model\Product $product)
    {
        $filterArray = [];
        $attributes = $this->eavConfig->getEntityAttributes(\Magento\Catalog\Model\Product::ENTITY, $product);

        foreach ($request as $code => $value) {
            if (array_key_exists($code, $attributes)) {
                $attribute = $attributes[$code];

                if ($attribute->getId() && $this->canReplaceImageWithSwatch($attribute)) {
                    $filterArray[$code] = $this->swatchHelperData->getOptionIds($attribute, $value);
                }
            }
        }

        return $filterArray;
    }

    /**
     * Load simple product variation of a given configurable product with swatches.
     * (copy/paste of parent method).
     *
     * @param Product $parentProduct Parent configurable product.
     * @param array   $filterArray   Swatch attributes values.
     *
     * @return bool|Product
     */
    private function loadSimpleVariation(Product $parentProduct, array $filterArray)
    {
        $childProduct = $this->swatchHelperData->loadVariationByFallback($parentProduct, $filterArray);
        if ($childProduct && !$childProduct->getImage()) {
            $childProduct = $this->swatchHelperData->loadFirstVariationWithImage($parentProduct, $filterArray);
        }
        if (!$childProduct) {
            $childProduct = $parentProduct;
        }

        return $childProduct;
    }

    /**
     * Check if we can replace original image with swatch image on catalog/category/list page
     * (copy/paste of parent method).
     *
     * @param Attribute $attribute Swatch attribute.
     *
     * @return bool
     */
    private function canReplaceImageWithSwatch($attribute)
    {
        $result = true;
        if (!$this->swatchHelperData->isSwatchAttribute($attribute)) {
            $result = false;
        }

        if (!$attribute->getUsedInProductListing()
            || !$attribute->getIsFilterable()
            || !$attribute->getData('update_product_preview_image')
        ) {
            $result = false;
        }

        return $result;
    }
}
