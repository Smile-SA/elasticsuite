<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteSwatches\Model\Plugin;

use Magento\Eav\Model\Entity\Attribute;

/**
 * Plugin that allow to select the right product image when a filter is selected.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteSwatches
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ProductImage extends \Magento\Swatches\Model\Plugin\ProductImage
{
    /**
     * Constructor.
     *
     * @param \Smile\ElasticSuiteSwatches\Helper\Swatches $swatchesHelperData Swatch helper.
     * @param \Magento\Eav\Model\Config                   $eavConfig          Product EAV configuration.
     * @param \Magento\Framework\App\Request\Http         $request            HTTP Request.
     */
    public function __construct(
        \Smile\ElasticSuiteSwatches\Helper\Swatches $swatchesHelperData,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($swatchesHelperData, $eavConfig, $request);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilterArray(array $request)
    {
        $filterArray = parent::getFilterArray($request);
        $attributeCodes = $this->eavConfig->getEntityAttributeCodes(\Magento\Catalog\Model\Product::ENTITY);

        foreach ($request as $code => $value) {
            if (in_array($code, $attributeCodes)) {
                $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $code);
                if ($attribute->getId() && $this->canReplaceImageWithSwatch($attribute)) {
                    $filterArray[$code] = $this->getOptionIds($attribute, $value);
                }
            }
        }

        return $filterArray;
    }

    /**
     * Retrive options ids from a labels array.
     *
     * @param Attribute $attribute Attribute.
     * @param string[]  $labels    Labels
     *
     * @return integer[]
     */
    private function getOptionIds(Attribute $attribute, $labels)
    {
        $optionIds = [];

        if (!is_array($labels)) {
            $labels = [$labels];
        }

        $options = $attribute->getSource()->getAllOptions();

        foreach ($labels as $label) {
            foreach ($options as $option) {
                if ($option['label'] == $label) {
                    $optionIds[] = $option['value'];
                }
            }
        }

        return $optionIds;
    }
}
