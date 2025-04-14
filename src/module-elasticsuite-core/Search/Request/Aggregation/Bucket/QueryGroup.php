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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
*/

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Query group aggregations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @param string              $name         Bucket name.
     * @param QueryInterface[]    $queries      Query group children queries.
     * @param MetricInterface[]   $metrics      Bucket metrics.
     * @param BucketInterface[]   $childBuckets Child buckets.
     * @param PipelineInterface[] $pipelines    Bucket pipelines.
     * @param string|null         $nestedPath   Nested path for nested bucket.
     * @param QueryInterface|null $filter       Bucket filter.
     * @param QueryInterface|null $nestedFilter Nested filter for the bucket.
     */
    public function __construct(
        string $name,
        array $queries,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        ?string $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null
    ) {
        parent::__construct($name, $name, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);
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
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
