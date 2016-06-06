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
use Magento\Framework\ObjectManagerInterface;

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
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $bucketBuilderClasses = [
        BucketInterface::TYPE_TERM        => 'Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\Term',
        BucketInterface::TYPE_HISTOGRAM   => 'Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\Histogram',
        BucketInterface::TYPE_QUERY_GROUP => 'Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\QueryGroup',
    ];


    /**
     * Constructor.
     *
     * @param ObjectManager $objectManager Object manager instance.
     * @param QueryBuilder  $queryBuilder  Query builder used to build queries inside sort orders.
     */
    public function __construct(ObjectManagerInterface $objectManager, QueryBuilder $queryBuilder)
    {
        $this->objectManager = $objectManager;
        $this->queryBuilder  = $queryBuilder;
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
     * @return object
     */
    private function getBuilder($bucketType)
    {
        if (isset($this->bucketBuilderClasses[$bucketType])) {
            $builderClass = $this->bucketBuilderClasses[$bucketType];
            $builder = $this->objectManager->get($builderClass, ['builder' => $this]);
        }

        return $builder;
    }
}
