<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Helper;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

/**
 * ElasticSuite swatches helper.
 *
 * Allow to load swatche images from a multivalued attribute filter.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Swatches extends \Magento\Swatches\Helper\Data
{
    /**
     * {@inheritDoc}
     */
    public function loadVariationByFallback($parentProduct, array $attributes)
    {
        $parentProduct = $this->createSwatchProduct($parentProduct);
        if (! $parentProduct) {
            return false;
        }

        $productCollection = $this->productCollectionFactory->create();
        $this->addFilterByParent($productCollection, $parentProduct->getId());

        $resultAttributesToFilter = $attributes;

        $this->addFilterByAttributes($productCollection, $resultAttributesToFilter);

        $variationProduct = $productCollection->getFirstItem();
        if ($variationProduct && $variationProduct->getId()) {
            return $this->productRepository->getById($variationProduct->getId());
        }

        return false;
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
