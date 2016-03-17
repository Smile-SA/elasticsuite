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
use Smile\ElasticSuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

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
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param AggregationFactory $aggregationFactory Factory used to instantiate buckets.
     * @param MetricFactory      $metricFactory      Factory used to instantiate buckets metrics.
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
     * @param ContainerConfigurationInterface $containerConfiguration Search request configuration
     * @param array                           $aggregations           Facet definitions.
     * @param array                           $filters                Facet filters to be added to buckets.
     *
     * @return BucketInterface[]
     */
    public function buildAggregations(
        ContainerConfigurationInterface $containerConfiguration,
        array $aggregations,
        array $filters
    ) {
        $buckets = [];

        $mapping = $containerConfiguration->getMapping();

        foreach ($aggregations as $fieldName => $aggregationParams) {
            $field = $mapping->getField($fieldName);

            if ($field == null) {
                throw new \LogicException("Field {$fieldName} does not exists in mapping.");
            }

            $bucketType = $aggregationParams['type'];
            $bucketParams = $this->getBucketParams($field, $aggregationParams, $filters);

            if (isset($bucketParams['filter'])) {
                $bucketParams['filter'] = $this->createFilter($containerConfiguration, $bucketParams['filter']);
            }

            if (isset($bucketParams['nestedFilter'])) {
                $nestedFilter = $this->createFilter($containerConfiguration, $bucketParams['nestedFilter']);
                $bucketParams['nestedFilter'] = $nestedFilter->getQuery();
            }

            $buckets[] = $this->aggregationFactory->create($bucketType, $bucketParams);
        }

        return $buckets;
    }

    /**
     * Create a QueryInterface for a filter using the query builder.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search container configuration
     * @param array                           $filters                Filters definition.
     *
     * @return QueryInterface
     */
    private function createFilter(ContainerConfigurationInterface $containerConfiguration, array $filters)
    {
        return $this->queryBuilder->createFilters($containerConfiguration, $filters);
    }

    /**
     * Preprocess aggregations params before they are used into the aggregation factory.
     *
     * @param FieldInterface $field             Bucket field.
     * @param array          $aggregationParams Aggregation params.
     * @param array          $filters           Filter applied to the search request.
     *
     * @return array
     */
    private function getBucketParams(FieldInterface $field, array $aggregationParams, array $filters)
    {
        $bucketField = $field->getMappingProperty(FieldInterface::ANALYZER_UNTOUCHED);

        if ($bucketField == null) {
            throw new \LogicException("Unable to init the filter field for {$field->getName()}");
        }

        $bucketParams = [
            'field'   => $bucketField,
            'name'    => $field->getName() . '_bucket',
            'metrics' => [],
            'filter' => array_diff_key($filters, [$field->getName() => true]),
        ];

        $bucketParams += $aggregationParams['config'];

        if (empty($bucketParams['filter'])) {
            unset($bucketParams['filter']);
        }

        if ($field->isNested() && !isset($bucketParams['nestedPath'])) {
            $bucketParams['nestedPath'] = $field->getNestedPath();
        } elseif ($field->isNested() == false && isset($bucketParams['nestedPath'])) {
            unset($bucketParams['nestedPath']);
        }

        return $bucketParams;
    }
}
