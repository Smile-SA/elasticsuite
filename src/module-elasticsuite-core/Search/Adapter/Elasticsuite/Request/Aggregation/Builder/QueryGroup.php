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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;

/**
 * Build an ES filters aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryGroup implements BuilderInterface
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param QueryBuilder $queryBuilder Search query builder.
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Build the aggregation.
     *
     * @param BucketInterface $bucket Term bucket.
     *
     * @return array
     */
    public function buildBucket(BucketInterface $bucket)
    {
        if ($bucket->getType() !== BucketInterface::TYPE_QUERY_GROUP) {
            throw new \InvalidArgumentException("Query builder : invalid aggregation type {$bucket->getType()}.");
        }

        $filters    = [];

        foreach ($bucket->getQueries() as $value => $query) {
            $filters[$value] = $this->queryBuilder->buildQuery($query);
        }

        return ['filters' => ['filters' => $filters]];
    }
}
