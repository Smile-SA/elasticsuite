<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build aggregation from the mapping.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationBuilder
{
    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MetricFactory
     */
    private $metricFactory;

    /**
     * Constructor.
     *
     * @param AggregationFactory $aggregationFactory Factory used to instantiate buckets.
     * @param MetricFactory      $metricFactory      Factory used to instantiate metrics.
     * @param QueryBuilder       $queryBuilder       Factory used to create queries inside filtered or nested aggs.
     */
    public function __construct(
        AggregationFactory $aggregationFactory,
        MetricFactory $metricFactory,
        QueryBuilder $queryBuilder
    ) {
        $this->aggregationFactory = $aggregationFactory;
        $this->metricFactory      = $metricFactory;
        $this->queryBuilder       = $queryBuilder;
    }

    /**
     * Build the list of buckets from the mapping.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration
     * @param array                           $aggregations    Facet definitions.
     * @param array                           $filters         Facet filters to be added to buckets.
     *
     * @return BucketInterface[]
     */
    public function buildAggregations(ContainerConfigurationInterface $containerConfig, array $aggregations, array $filters)
    {
        $buckets = [];

        foreach ($aggregations as $fieldName => $aggParams) {
            $buckets[] = is_object($aggParams) ? $aggParams : $this->buildAggregation($containerConfig, $filters, $fieldName, $aggParams);
        }

        return $buckets;
    }

    /**
     * Build the list of buckets from the mapping.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration
     * @param array                           $filters         Facet filters to be added to buckets.
     * @param string                          $fieldName       Current field name.
     * @param array                           $aggParams       Current aggregation params.
     *
     * @return BucketInterface[]
     */
    private function buildAggregation(ContainerConfigurationInterface $containerConfig, $filters, $fieldName, $aggParams)
    {
        $mapping    = $containerConfig->getMapping();
        $bucketType = $aggParams['type'];

        try {
            $field        = $mapping->getField($fieldName);
            $bucketParams = $this->getBucketParams($field, $aggParams, $filters);

            if (isset($bucketParams['filter'])) {
                $bucketParams['filter'] = $this->createFilter($containerConfig, $bucketParams['filter']);
            }

            if (isset($bucketParams['nestedFilter'])) {
                $nestedFilter = $this->createFilter($containerConfig, $bucketParams['nestedFilter'], $bucketParams['nestedPath']);
                $bucketParams['nestedFilter'] = $nestedFilter;
            }

            if (isset($bucketParams['childBuckets'])) {
                $bucketParams['childBuckets'] = $this->buildAggregations($containerConfig, $bucketParams['childBuckets'], []);
            }
        } catch (\Exception $e) {
            $bucketParams = $aggParams['config'];
        }

        $bucketParams['metrics'] = $this->getMetrics($containerConfig, $aggParams);

        return $this->aggregationFactory->create($bucketType, $bucketParams);
    }

    /**
     * Create a QueryInterface for a filter using the query builder.
     *
     * @param ContainerConfigurationInterface $containerConfig Search container configuration
     * @param array                           $filters         Filters definition.
     * @param string|null                     $currentPath     Current nested path or null.
     *
     * @return QueryInterface
     */
    private function createFilter(ContainerConfigurationInterface $containerConfig, array $filters, $currentPath = null)
    {
        return $this->queryBuilder->create($containerConfig, $filters, $currentPath);
    }

    /**
     * Preprocess aggregations params before they are used into the aggregation factory.
     *
     * @param FieldInterface $field     Bucket field.
     * @param array          $aggParams Aggregation params.
     * @param array          $filters   Filter applied to the search request.
     *
     * @return array
     */
    private function getBucketParams(FieldInterface $field, array $aggParams, array $filters)
    {
        $bucketField = $field->getMappingProperty(FieldInterface::ANALYZER_UNTOUCHED);

        if ($bucketField === null) {
            throw new \LogicException("Unable to init the filter field for {$field->getName()}");
        }

        $bucketParams = [
            'field'   => $bucketField,
            'name'    => isset($aggParams['config']['name']) ? $aggParams['config']['name'] : $field->getName(),
            'filter' => array_diff_key($filters, [$field->getName() => true]),
        ];

        $bucketParams += $aggParams['config'];

        if (empty($bucketParams['filter'])) {
            unset($bucketParams['filter']);
        }

        if ($field->isNested()) {
            $bucketParams['nestedPath'] = $field->getNestedPath();
        } elseif (isset($bucketParams['nestedPath'])) {
            unset($bucketParams['nestedPath']);
        }

        return $bucketParams;
    }

    /**
     * Build buckets metric.
     *
     * @param ContainerConfigurationInterface $containerConfig Container config.
     * @param array                           $aggParams       Aggregation params.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Aggregation\Metric[]
     */
    private function getMetrics(ContainerConfigurationInterface $containerConfig, array $aggParams)
    {
        $metrics = [];
        if (isset($aggParams['config']['metrics'])) {
            foreach ($aggParams['config']['metrics'] as $metricName => $metricConfig) {
                try {
                    $field = $containerConfig->getMapping()->getField($metricConfig['field']);
                    $metricConfig['field'] = $field->getName();
                } catch (\Exception $e) {
                    ;
                }

                $metrics[] = $this->metricFactory->create(['name' => $metricName] + $metricConfig);
            }
        }

        return $metrics;
    }
}
