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

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Term Bucket implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Term extends AbstractBucket
{
    /**
     * @var integer
     */
    private $size;

    /**
     * @var string
     */
    private $sortOrder;

    /**
     * @var array
     */
    private $include;

    /**
     * @var array
     */
    private $exclude;

    /**
     * @var integer
     */
    private $minDocCount;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string              $name         Bucket name.
     * @param string              $field        Bucket field.
     * @param MetricInterface[]   $metrics      Bucket metrics.
     * @param BucketInterface[]   $childBuckets Child buckets.
     * @param PipelineInterface[] $pipelines    Bucket pipelines.
     * @param string|null         $nestedPath   Nested path for nested bucket.
     * @param QueryInterface|null $filter       Bucket filter.
     * @param QueryInterface|null $nestedFilter Nested filter for the bucket.
     * @param integer             $size         Bucket size.
     * @param string              $sortOrder    Bucket sort order.
     * @param array               $include      Include bucket filter.
     * @param array               $exclude      Exclude bucket filter.
     * @param int|null            $minDocCount  Min doc count bucket filter.
     */
    public function __construct(
        string $name,
        string $field,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        ?string $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null,
        int $size = 0,
        string $sortOrder = BucketInterface::SORT_ORDER_COUNT,
        array $include = [],
        array $exclude = [],
        ?int $minDocCount = null
    ) {
        parent::__construct($name, $field, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);

        $this->size      = $size > 0 && $size < self::MAX_BUCKET_SIZE ? $size : self::MAX_BUCKET_SIZE;
        $this->sortOrder = $sortOrder;
        $this->include   = $include;
        $this->exclude   = $exclude;
        $this->minDocCount   = $minDocCount;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return BucketInterface::TYPE_TERM;
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

    /**
     * Bucket include filter.
     *
     * @return array
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * Bucket exclude filter.
     *
     * @return array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * Bucket min doc count filter.
     *
     * @return int
     */
    public function getMinDocCount()
    {
        return $this->minDocCount;
    }
}
