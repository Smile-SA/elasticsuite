<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteSwatches\Model\Plugin;

use Magento\Eav\Model\Entity\Attribute;

/**
 * Plugin that allow to select the right product image when a filter is selected.
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
     * @deprecated since Magento 2.1.6
     */
    protected function getFilterArray(array $request)
    {
        $filterArray = parent::getFilterArray($request);

        $attributeCodes = $this->eavConfig->getEntityAttributeCodes(\Magento\Catalog\Model\Product::ENTITY);

        foreach ($request as $code => $value) {
            if (in_array($code, $attributeCodes)) {
                $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $code);

                if (isset($filterArray[$code]) && !is_array($filterArray[$code])) {
                    $filterArray[$code] = [$filterArray[$code]];
                }

                if ($attribute->getId() && $this->canReplaceImageWithSwatch($attribute)) {
                    $filterArray[$code][] = $this->swatchHelperData->getOptionIds($attribute, $value);
                }
            }
        }

        return $filterArray;
    }
}
