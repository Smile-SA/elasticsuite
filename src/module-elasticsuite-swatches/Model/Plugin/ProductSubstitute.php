<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteSwatches\Model\Plugin;

use Smile\ElasticsuiteSwatches\Helper\Swatches;

/**
 * ProductSubstitute Plugin. Used to load Swatches variations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @since    Magento 2.1.6
 */
class ProductSubstitute
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Smile\ElasticsuiteSwatches\Helper\Swatches
     */
    private $swatchHelper;

    /**
     * ProductSubstitute constructor.
     *
     * @param \Magento\Eav\Model\Config                   $config       EAV Config
     * @param \Smile\ElasticsuiteSwatches\Helper\Swatches $swatchHelper Swatch Helper
     */
    public function __construct(\Magento\Eav\Model\Config $config, Swatches $swatchHelper)
    {
        $this->eavConfig    = $config;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * Build proper array for swatches rendering. Especially in product listing where values may come as label
     * instead of option Ids.
     *
     * @param \Magento\Swatches\Model\ProductSubstitute $productSubstitute Original ProductSubstitute class
     * @param \Closure                                  $proceed           ProductSubstitute::getFilterArray()
     * @param array                                     $request           Request
     *
     * @return mixed
     */
    public function aroundGetFilterArray(
        \Magento\Swatches\Model\ProductSubstitute $productSubstitute,
        \Closure $proceed,
        array $request
    ) {
        $filterArray = $proceed($request);

        $attributeCodes = $this->eavConfig->getEntityAttributeCodes(\Magento\Catalog\Model\Product::ENTITY);

        foreach ($request as $code => $value) {
            if (in_array($code, $attributeCodes)) {
                $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $code);

                if (isset($filterArray[$code]) && !is_array($filterArray[$code])) {
                    $filterArray[$code] = [$filterArray[$code]];
                }

                if ($attribute->getId() && $productSubstitute->canReplaceImageWithSwatch($attribute)) {
                    $filterArray[$code][] = $this->swatchHelper->getOptionIds($attribute, $value);
                }
            }
        }

        return $filterArray;
    }
}
