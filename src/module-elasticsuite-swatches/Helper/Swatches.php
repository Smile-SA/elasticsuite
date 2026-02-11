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
namespace Smile\ElasticsuiteSwatches\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute;

/**
 * ElasticSuite swatches helper.
 * Allow to load swatches images from a multivalued attribute filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Swatches extends \Magento\Swatches\Helper\Data
{
    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     * {@inheritDoc}
     */
    public function loadVariationByFallback(Product $parentProduct, array $attributes)
    {
        $variation = false;

        if ($this->isProductHasSwatch($parentProduct) && $parentProduct->getDocumentSource() !== null) {
            $variation = $this->loadVariationsFromSearchIndex($parentProduct, $attributes);
        } else {
            $productCollection = $this->productCollectionFactory->create();
            $this->addFilterByParent($productCollection, $parentProduct->getId());

            $configurableAttributes = $this->getAttributesFromConfigurable($parentProduct);
            $allAttributesArray     = [];

            foreach ($configurableAttributes as $attribute) {
                $allAttributesArray[$attribute['attribute_code']] = $attribute['default_value'];
                if ($attribute->usesSource() && isset($attributes[$attribute->getAttributeCode()])) {
                    // If value is the attribute label, replace it by the optionId.
                    $optionId = $attribute->getSource()->getOptionId($attributes[$attribute->getAttributeCode()]);
                    if ($optionId) {
                        $attributes[$attribute->getAttributeCode()] = $optionId;
                    }
                }
            }

            $resultAttributesToFilter = array_merge(
                $attributes,
                array_diff_key($allAttributesArray, $attributes)
            );

            $this->addFilterByAttributes($productCollection, $resultAttributesToFilter);

            $variationProduct = $productCollection->getFirstItem();

            if ($variationProduct && $variationProduct->getId()) {
                $variation = $this->productRepository->getById($variationProduct->getId());
            }
        }

        return $variation;
    }

    /**
     * Load first variation with image
     * Override: Manage fallback for child products with images when multiple swatch values
     * are selected and the current child does not have an image.
     *
     * @param ProductInterface $configurableProduct Configurable product.
     * @param array            $requiredAttributes  Attributes to match in the child product.
     *
     * @return bool|ProductInterface
     */
    public function loadFirstVariationWithImage(ProductInterface $configurableProduct, array $requiredAttributes)
    {
        if ($this->isProductHasSwatch($configurableProduct)) {
            $usedProducts = $configurableProduct->getTypeInstance()->getUsedProducts($configurableProduct);

            foreach ($usedProducts as $simpleProduct) {
                foreach ($requiredAttributes as $attributeCode => $requiredValues) {
                    if (!in_array($simpleProduct->getData($attributeCode), $requiredValues)) {
                        break 2;
                    }
                }

                if ($this->isMediaAvailable($simpleProduct, 'image')) {
                    return $simpleProduct;
                }
            }
        }

        return false;
    }

    /**
     * Retrive options ids from a labels array.
     *
     * @param Attribute $attribute Attribute.
     * @param string[]  $labels    Labels
     *
     * @return integer[]
     */
    public function getOptionIds(Attribute $attribute, $labels)
    {
        $optionIds = [];

        if (!is_array($labels)) {
            $labels = [$labels];
        }

        $options = $attribute->getSource()->getAllOptions();

        foreach ($labels as $label) {
            foreach ($options as $option) {
                if ($option['label'] == $label) {
                    $optionIds[] = (int) $option['value'];
                }
            }
        }

        return $optionIds;
    }

    /**
     * {@inheritDoc}
     */
    protected function addFilterByAttributes(ProductCollection $productCollection, array $attributes)
    {
        foreach ($attributes as $code => $option) {
            if (!is_array($option)) {
                $option = [$option];
            }
            $productCollection->addAttributeToFilter($code, ['in' => $option]);
        }
    }

    /**
     * Load variations for a given product with data coming from the search index.
     *
     * @param Product $parentProduct Parent Product
     * @param array   $attributes    Attributes
     *
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function loadVariationsFromSearchIndex(Product $parentProduct, array $attributes)
    {
        $documentSource = $parentProduct->getDocumentSource();
        $childrenIds    = isset($documentSource['children_ids']) ? $documentSource['children_ids'] : [];
        $variation      = false;

        if (!empty($childrenIds)) {
            $childrenIds = array_map('intval', $childrenIds);

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addIdFilter($childrenIds);

            $configurableAttributes = $this->getAttributesFromConfigurable($parentProduct);
            $allAttributesArray     = [];

            foreach ($configurableAttributes as $attribute) {
                foreach ($attribute->getOptions() as $option) {
                    $allAttributesArray[$attribute['attribute_code']][] = (int) $option->getValue();
                }
            }

            $resultAttributesToFilter = array_merge($attributes, array_diff_key($allAttributesArray, $attributes));

            $this->addFilterByAttributes($productCollection, $resultAttributesToFilter);

            $variationProduct = $productCollection->getFirstItem();

            if ($variationProduct && $variationProduct->getId()) {
                $variation = $this->productRepository->getById($variationProduct->getId());
            }
        }

        return $variation;
    }

    /**
     * Filter a collection by its parent.
     * Inherited method since it's private in the parent.
     *
     * @param ProductCollection $productCollection Product Collection
     * @param integer           $parentId          Parent Product Id
     *
     * @return void
     */
    private function addFilterByParent(ProductCollection $productCollection, $parentId)
    {
        $tableProductRelation = $productCollection->getTable('catalog_product_relation');
        $productCollection->getSelect()->join(
            ['pr' => $tableProductRelation],
            'e.entity_id = pr.child_id'
        )->where('pr.parent_id = ?', $parentId);
    }

    /**
     * Check is media attribute available
     * Override: Copy this method because native one is private.
     *
     * @param Product $product       Product.
     * @param string  $attributeCode Media attribute code.
     *
     * @return bool
     */
    private function isMediaAvailable(Product $product, string $attributeCode): bool
    {
        $isAvailable = false;

        $mediaGallery = $product->getMediaGalleryEntries();
        foreach ($mediaGallery as $mediaEntry) {
            if (in_array($attributeCode, $mediaEntry->getTypes(), true)) {
                $isAvailable = !$mediaEntry->isDisabled();
                break;
            }
        }

        return $isAvailable;
    }
}
