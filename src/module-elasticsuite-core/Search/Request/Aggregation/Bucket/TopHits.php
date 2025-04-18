<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Top Hits aggregation implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class TopHits extends AbstractBucket
{
    /**
     * @var array
     */
    private $sourceFields;

    /**
     * @var string
     */
    private $sortOrder;

    /**
     * @var integer
     */
    private $size;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string              $name         Bucket name.
     * @param array               $sourceFields Source fields to fetch from the hits.
     * @param int                 $size         Bucket size.
     * @param string              $sortOrder    Bucket Sort Order.
     * @param MetricInterface[]   $metrics      Bucket metrics.
     * @param BucketInterface[]   $childBuckets Child buckets.
     * @param PipelineInterface[] $pipelines    Bucket pipelines.
     * @param string              $nestedPath   Nested path for nested bucket.
     * @param QueryInterface      $filter       Bucket filter.
     * @param QueryInterface      $nestedFilter Nested filter for the bucket.
     */
    public function __construct(
        $name,
        array $sourceFields = [],
        int $size = 1,
        $sortOrder = BucketInterface::SORT_ORDER_COUNT,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null
    ) {
        parent::__construct($name, $name, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);
        $this->sourceFields = $sourceFields;
        $this->sortOrder    = $sortOrder;
        $this->size         = $size;
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType()
    {
        return BucketInterface::TYPE_TOP_HITS;
    }

    /**
     * Get source fields to fetch
     *
     * @return array
     */
    public function getSource()
    {
        return $this->sourceFields;
    }

    /**
     * Bucket size.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Bucket sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
