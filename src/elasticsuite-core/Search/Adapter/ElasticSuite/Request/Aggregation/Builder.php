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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation;

use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
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
     *
     * @todo Implement missing types :
     * BucketInterface::TYPE_HISTOGRAM
     * BucketInterface::TYPE_RANGE
     * BucketInterface::TYPE_DYNAMIC
     * BucketInterface::TYPE_DATE_HISTOGRAM
     */
    private $bucketBuilderClasses = [
        BucketInterface::TYPE_TERM      => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation\Builder\Term',
        BucketInterface::TYPE_HISTOGRAM => 'Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation\Builder\Histogram',
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
