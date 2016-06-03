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

namespace Smile\ElasticSuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;

/**
 * Query group aggregations.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryGroup extends AbstractBucket
{
    /**
     * @var QueryInterface[]
     */
    private $queries;

    /**
     * Constructor.
     *
     * @param string           $name         Bucket name.
     * @param QueryInterface[] $queries      Query group children queries.
     * @param Metric[]         $metrics      Bucket metrics.
     * @param string           $nestedPath   Nested path for nested bucket.
     * @param QueryInterface   $filter       Bucket filter.
     * @param QueryInterface   $nestedFilter Nested filter for the bucket.
     */
    public function __construct(
        $name,
        array $queries,
        array $metrics = [],
        $nestedPath = null,
        QueryInterface $filter = null,
        QueryInterface $nestedFilter = null
    ) {
        parent::__construct($name, $name, $metrics, $nestedPath, $filter, $nestedFilter);
        $this->queries = $queries;
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        throw new \LogicException("getField is not supported on query group aggregations.");
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return BucketInterface::TYPE_QUERY_GROUP;
    }

    /**
     * List of the queries of the query group.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
