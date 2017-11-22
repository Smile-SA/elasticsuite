<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
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
     * @var \Magento\Elasticsearch\Model\Adapter\Index\BuilderInterface[]
     */
    private $builders;

    /**
     * Constructor.
     *
     * @param QueryBuilder                                                  $queryBuilder Query builder used to build
     *                                                                                    queries inside sort orders.
     * @param \Magento\Elasticsearch\Model\Adapter\Index\BuilderInterface[] $builders     Aggregation builder implementations
     */
    public function __construct(QueryBuilder $queryBuilder, array $builders = [])
    {
        $this->queryBuilder = $queryBuilder;
        $this->builders     = $builders;
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
            $aggregation = $builder->buildBucket($bucket);
            $subAggregations = isset($aggregation['aggregations']) ? $aggregation['aggregations'] : [];

            if (!empty($bucket->getChildBuckets())) {
                $subAggregations = array_merge($subAggregations, $this->buildAggregations($bucket->getChildBuckets()));
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
     * @return \Magento\Elasticsearch\Model\Adapter\Index\BuilderInterface
     */
    private function getBuilder($bucketType)
    {
        if (!isset($this->builders[$bucketType])) {
            throw new \InvalidArgumentException("No builder found for aggregation type {$bucketType}.");
        }

        return $this->builders[$bucketType];
    }
}
