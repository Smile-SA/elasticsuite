<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\Aggregation;

use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Aggregation\MetricFactory;
use Magento\Framework\Search\Request\Aggregation\Metric;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Build aggregation from the mapping.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationBuilder
{
    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var MetricFactory
     */
    private $metricFactory;

    /**
     * Constructor.
     *
     * @param AggregationFactory $aggregationFactory Factory used to instantiate buckets.
     * @param MetricFactory      $metricFactory      Factory used to instantiate buckets metrics.
     */
    public function __construct(AggregationFactory $aggregationFactory, MetricFactory $metricFactory)
    {
        $this->aggregationFactory = $aggregationFactory;
        $this->metricFactory      = $metricFactory;
    }

    /**
     * Build the list of buckets from the mapping.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request configuration
     * @param array                           $filters                Facet filters to be added to buckets.
     *
     * @return BucketInterface[]
     */
    public function buildAggregations(ContainerConfigurationInterface $containerConfiguration, array $filters)
    {
        foreach ($containerConfiguration->getMapping()->getFields() as $mappingField) {
            if ($mappingField->isFacet($containerConfiguration->getName())) {
                $bucketField = $mappingField->getMappingProperty(FieldInterface::ANALYZER_UNTOUCHED);
                if ($bucketField) {
                    $bucketType = BucketInterface::TYPE_TERM;
                    $bucketParams = [
                        'field'   => $bucketField,
                        'name'    => $mappingField->getName() . '_bucket',
                        'metrics' => $this->getDefaultMetric(),
                    ];
                    $buckets[] = $this->aggregationFactory->create($bucketType, $bucketParams);
                }
            }
        }

        return $buckets;
    }

    /**
     * Build the default metric (count).
     *
     * @return Metric
     */
    private function getDefaultMetric()
    {
        return [$this->metricFactory->create(['type' => BucketInterface::FIELD_VALUE])];
    }
}
