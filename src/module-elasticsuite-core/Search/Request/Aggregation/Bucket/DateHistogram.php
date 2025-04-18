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
 * Date historgram bucket implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DateHistogram extends Histogram
{
    /**
     * @var string
     */
    private $calendarInterval;

    /**
     * @var string
     */
    private $fixedInterval;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string              $name             Bucket name.
     * @param string              $field            Bucket field.
     * @param MetricInterface[]   $metrics          Bucket metrics.
     * @param BucketInterface[]   $childBuckets     Child buckets.
     * @param PipelineInterface[] $pipelines        Bucket pipelines.
     * @param string              $nestedPath       Nested path for nested bucket.
     * @param QueryInterface      $filter           Bucket filter.
     * @param QueryInterface      $nestedFilter     Nested filter for the bucket.
     * @param integer             $interval         Histogram interval.
     * @param string              $calendarInterval Histogram interval.
     * @param string              $fixedInterval    Histogram interval.
     * @param integer             $minDocCount      Histogram min doc count.
     * @param array               $extendedBounds   Histogram extended bounds.
     */
    public function __construct(
        $name,
        $field,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null,
        $interval = "1d", // Deprecated.
        $calendarInterval = null,
        $fixedInterval = "1d",
        $minDocCount = 0,
        $extendedBounds = []
    ) {
        $this->calendarInterval = $calendarInterval;
        $this->fixedInterval    = $fixedInterval;
        parent::__construct(
            $name,
            $field,
            $metrics,
            $childBuckets,
            $pipelines,
            $nestedPath,
            $filter,
            $nestedFilter,
            $interval,
            $minDocCount,
            $extendedBounds
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return BucketInterface::TYPE_DATE_HISTOGRAM;
    }

    /**
     * Histogram interval.
     *
     * @return integer
     */
    public function getCalendarInterval()
    {
        return $this->calendarInterval;
    }

    /**
     * Histogram interval.
     *
     * @return integer
     */
    public function getFixedInterval()
    {
        return $this->fixedInterval;
    }
}
