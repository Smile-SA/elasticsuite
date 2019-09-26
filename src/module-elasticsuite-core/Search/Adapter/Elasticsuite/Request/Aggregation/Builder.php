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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var BuilderInterface[]
     */
    private $builders;

    /**
     * @var PipelineBuilderInterface[]
     */
    private $pipelineBuilders;

    /**
     * Constructor.
     *
     * @param QueryBuilder               $queryBuilder     Query builder used to build
     *                                                     Queries inside sort orders.
     * @param BuilderInterface[]         $builders         Aggregation builder implementations
     * @param PipelineBuilderInterface[] $pipelineBuilders Pipeline aggregation builder implementations
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        array $builders = [],
        array $pipelineBuilders = []
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->builders     = $builders;
        $this->pipelineBuilders = $pipelineBuilders;
    }

    /**
     * Build ES aggregations from search request buckets.
     *
     * @param BucketInterface[] $buckets Bucket to be converted into ES aggregations
     *
     * @return array
     */
    public function buildAggregations(array $buckets = [])
    {
        $aggregations = [];

        foreach ($buckets as $bucket) {
            $bucketType = $bucket->getType();
            $builder    = $this->getBuilder($bucketType);
            $aggregation     = $builder->buildBucket($bucket);
            $subAggregations = $aggregation['aggregations'] ?? [];

            if (!empty($bucket->getChildBuckets())) {
                $subAggregations = array_merge($subAggregations, $this->buildAggregations($bucket->getChildBuckets()));
            }

            foreach ($bucket->getMetrics() as $metric) {
                $metricDefinition = array_merge(['field' => $metric->getField()], $metric->getConfig() ?? []);
                $subAggregations[$metric->getName()] = [$metric->getType() => $metricDefinition];
            }

            foreach ($bucket->getPipelines() as $pipeline) {
                $pipelineType    = $pipeline->getType();
                $pipelineBuilder = $this->getPipelineBuilder($pipelineType);
                $pipelineAgg     = $pipelineBuilder->buildPipeline($pipeline);
                $subAggregations[$pipeline->getName()] = $pipelineAgg;
            }

            if (!empty($subAggregations)) {
                $aggregation['aggregations'] = $subAggregations;
            }

            if ($bucket->isNested()) {
                if ($bucket->getNestedFilter()) {
                    $aggregation = [
                        'filter'       => $this->queryBuilder->buildQuery($bucket->getNestedFilter()),
                        'aggregations' => [$bucket->getName() => $aggregation],
                    ];
                }

                $aggregation = [
                    'nested'       => ['path' => $bucket->getNestedPath()],
                    'aggregations' => [$bucket->getName() => $aggregation],
                ];
            }

            if ($bucket->getFilter()) {
                $aggregation = [
                    'filter'       => $this->queryBuilder->buildQuery($bucket->getFilter()),
                    'aggregations' => [$bucket->getName() => $aggregation],
                ];
            }

            $aggregations[$bucket->getName()] = $aggregation;
        }

        return $aggregations;
    }

    /**
     * Retrieve the builder used to convert a bucket into ES aggregation.
     *
     * @param string $bucketType Bucket type to be built.
     *
     * @return BuilderInterface
     */
    private function getBuilder($bucketType)
    {
        if (!isset($this->builders[$bucketType])) {
            throw new \InvalidArgumentException("No builder found for aggregation type {$bucketType}.");
        }

        return $this->builders[$bucketType];
    }

    /**
     * Retrieve the builder used to convert a pipeline into an ES aggregation.
     *
     * @param string $pipelineType Pipeline type to be built.
     *
     * @return PipelineBuilderInterface
     */
    private function getPipelineBuilder($pipelineType)
    {
        if (!isset($this->pipelineBuilders[$pipelineType])) {
            throw new \InvalidArgumentException("No builder found for pipeline aggregation type {$pipelineType}.");
        }

        return $this->pipelineBuilders[$pipelineType];
    }
}
