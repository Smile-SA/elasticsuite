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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation\Builder;

use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder as QueryBuilder;

/**
 * Build an ES filters aggregation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryGroup
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
        $filters    = [];

        foreach ($bucket->getQueries() as $value => $query) {
            $filters[$value] = $this->queryBuilder->buildQuery($query);
        }

        return ['filters' => ['filters' => $filters]];
    }
}
