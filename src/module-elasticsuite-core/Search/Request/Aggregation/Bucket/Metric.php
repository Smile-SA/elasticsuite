<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Top level metrics aggregation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Metric extends AbstractBucket
{
    /**
     * @var string
     */
    private $metricType;

    /**
     * @var array
     */
    private $config;

    /**
     * Metric constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string              $name         Bucket name.
     * @param string              $field        Bucket field.
     * @param MetricInterface[]   $metrics      Bucket metrics.
     * @param BucketInterface[]   $childBuckets Child buckets.
     * @param PipelineInterface[] $pipelines    Bucket pipelines.
     * @param string              $nestedPath   Nested path for nested bucket.
     * @param QueryInterface      $filter       Bucket filter.
     * @param QueryInterface      $nestedFilter Nested filter for the bucket.
     * @param string              $metricType   Metric type.
     * @param array               $config       Metric extra config.
     */
    public function __construct(
        $name,
        $field,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        $nestedPath = null,
        QueryInterface $filter = null,
        QueryInterface $nestedFilter = null,
        $metricType = MetricInterface::TYPE_STATS,
        $config = []
    ) {
        parent::__construct($name, $field, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);

        $this->metricType = $metricType;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return self::TYPE_METRIC;
    }

    /**
     * Return metric type.
     *
     * @return string
     */
    public function getMetricType()
    {
        return $this->metricType;
    }

    /**
     * Return metric extra config.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
