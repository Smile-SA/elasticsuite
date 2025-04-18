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
 * Historgram bucket implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Histogram extends AbstractBucket
{
    /**
     * @var integer
     */
    private $interval;

    /**
     * @var integer
     */
    private $minDocCount;

    /**
     * @var array
     */
    private $extendedBounds;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string              $name           Bucket name.
     * @param string              $field          Bucket field.
     * @param MetricInterface[]   $metrics        Bucket metrics.
     * @param BucketInterface[]   $childBuckets   Child buckets.
     * @param PipelineInterface[] $pipelines      Bucket pipelines.
     * @param string              $nestedPath     Nested path for nested bucket.
     * @param QueryInterface|null $filter         Bucket filter.
     * @param QueryInterface|null $nestedFilter   Nested filter for the bucket.
     * @param integer             $interval       Histogram interval.
     * @param integer             $minDocCount    Histogram min doc count.
     * @param array               $extendedBounds Histogram extended bounds.
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
        $interval = 1,
        $minDocCount = 0,
        $extendedBounds = []
    ) {
        parent::__construct($name, $field, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);
        $this->interval    = $interval;
        $this->minDocCount = $minDocCount;
        $this->extendedBounds = $extendedBounds;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return BucketInterface::TYPE_HISTOGRAM;
    }

    /**
     * Histogram interval.
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Histograms min doc count.
     *
     * @return integer
     */
    public function getMinDocCount()
    {
        return $this->minDocCount;
    }

    /**
     * Get histogram extended bounds.
     *
     * @return array
     */
    public function getExtendedBounds()
    {
        return $this->extendedBounds;
    }
}
