<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Smile\ElasticsuiteCatalog\Helper\Slider as SliderHelper;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier\Statistics\AttributeFilterInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

/**
 * Statistics modifier for filterable attributes provider.
 * Adds extended stats top-level metrics for supported attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Statistics implements ModifierInterface
{
    /**
     * @var SliderHelper
     */
    private $sliderHelper;

    /**
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * @var AttributeFilterInterface
     */
    private $attributeFilter;

    /**
     * @var Attribute[]
     */
    private $applicableAttributes = [];

    /**
     * Statistics constructor.
     *
     * @param SliderHelper             $sliderHelper       Price slider helper.
     * @param RequestFieldMapper       $requestFieldMapper Request field mapper.
     * @param AttributeFilterInterface $attributeFilter    Attribute filter.
     */
    public function __construct(
        SliderHelper $sliderHelper,
        RequestFieldMapper $requestFieldMapper,
        AttributeFilterInterface $attributeFilter
    ) {
        $this->sliderHelper = $sliderHelper;
        $this->requestFieldMapper = $requestFieldMapper;
        $this->attributeFilter = $attributeFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAttributes($storeId, $requestName, $attributes, $query, $filters, $queryFilters)
    {
        $this->applicableAttributes = [];

        if ($this->sliderHelper->isAdaptiveSliderEnabled()) {
            /** @var Attribute[] $attributes */
            foreach ($attributes as $attribute) {
                if ($this->attributeFilter->filterAttribute($attribute)) {
                    $this->applicableAttributes[] = $attribute;
                }
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAggregations($storeId, $requestName, $aggregations, $query, $filters, $queryFilters)
    {
        if ($this->sliderHelper->isAdaptiveSliderEnabled()) {
            foreach ($this->applicableAttributes as $attribute) {
                $field = $this->requestFieldMapper->getMappedFieldName($attribute->getAttributeCode());
                if (array_key_exists($field, $aggregations)) {
                    $aggregation = $aggregations[$field];
                    $aggMetricName = $this->sliderHelper->getStatsAggregation($field);
                    if (empty($aggMetricName)) {
                        continue;
                    }
                    $aggMetricConfig = [
                        'name' => $aggMetricName,
                        'type' => BucketInterface::TYPE_METRIC,
                        'field' => $field,
                        'metricType' => MetricInterface::TYPE_EXTENDED_STATS,
                        'config'    => [
                            'sigma' => SliderHelper::STD_DEVIATION_SIGMA,
                        ],
                    ];
                    if (array_key_exists('nestedPath', $aggregation)) {
                        $aggMetricConfig['nestedPath'] = $aggregation['nestedPath'];
                    }
                    if (array_key_exists('nestedFilter', $aggregation)) {
                        $aggMetricConfig['nestedFilter'] = $aggregation['nestedFilter'];
                    }
                    $aggregations[$aggMetricName] = $aggMetricConfig;
                }
            }
        }

        return $aggregations;
    }
}
