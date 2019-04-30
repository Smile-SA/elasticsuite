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
namespace Smile\ElasticsuiteSwatches\Plugin\Search\Request\Product\Attribute;

/**
 * Plugin to set default facet_max_size of swatches attributes to 0 for layered navigation.
 *
 * @category Smile
 * @package  Smile\ElasticSuiteSwatches
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AggregationResolver
{
    /**
     * @var \Magento\Swatches\Helper\Data
     */
    private $swatchHelper;

    /**
     * FrontPlugin constructor.
     *
     * @param \Magento\Swatches\Helper\Data $swatchHelper Swatch Attribute Helper
     */
    public function __construct(\Magento\Swatches\Helper\Data $swatchHelper)
    {
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * Set default facet size to 0 for swatches attributes before adding it as aggregation.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver $subject   Aggregation Resolver
     * @param array                                                                           $result    Aggregation Config
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute                              $attribute Attribute
     *
     * @return array
     */
    public function afterGetAggregationData(
        \Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationResolver $subject,
        $result,
        $attribute
    ) {
        if ($this->swatchHelper->isSwatchAttribute($attribute)) {
            $result['size'] = 0;
        }

        return $result;
    }
}
