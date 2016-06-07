<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteSwatches\Helper;

use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

/**
 * ElasticSuite swatches helper.
 *
 * Allow to load swatche images from a multivalued attribute filter.
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
            $documentSource = $parentProduct->getDocumentSource();
            $childrenIds = isset($documentSource['children_ids']) ? $documentSource['children_ids'] : [];

            if (!empty($childrenIds)) {
                $productCollection = $this->productCollectionFactory->create();
                $productCollection->addIdFilter($childrenIds);

                $configurableAttributes = $this->getAttributesFromConfigurable($parentProduct);
                $allAttributesArray = [];

                foreach ($configurableAttributes as $attribute) {
                    foreach ($attribute->getOptions() as $option) {
                        $allAttributesArray[$attribute['attribute_code']][] = $option->getValue();
                    }
                }

                $resultAttributesToFilter = array_merge($attributes, array_diff_key($allAttributesArray, $attributes));

                $this->addFilterByAttributes($productCollection, $resultAttributesToFilter);

                $variationProduct = $productCollection->getFirstItem();

                if ($variationProduct && $variationProduct->getId()) {
                    $variation = $this->productRepository->getById($variationProduct->getId());
                }
            }
        } else {
            $variation = parent::loadVariationByFallback($parentProduct, $attributes);
        }

        return $variation;
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
}
