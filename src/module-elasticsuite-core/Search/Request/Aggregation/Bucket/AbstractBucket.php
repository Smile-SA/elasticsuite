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
 * Abstract bucket implementation.
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractBucket implements BucketInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $field;

    /**
     * @var MetricInterface[]
     */
    private $metrics;

    /**
     * @var BucketInterface[]
     */
    private $childBuckets;

    /**
     * @var PipelineInterface[]
     */
    private $pipelines;

    /**
     * @var string
     */
    private $nestedPath;

    /**
     * @var QueryInterface|null
     */
    private $filter;

    /**
     * @var QueryInterface|null
     */
    private $nestedFilter;

    /**
     * Constructor.
     *
     * @param string              $name         Bucket name.
     * @param string              $field        Bucket field.
     * @param MetricInterface[]   $metrics      Bucket metrics.
     * @param BucketInterface[]   $childBuckets Child buckets.
     * @param PipelineInterface[] $pipelines    Bucket pipelines.
     * @param string|null         $nestedPath   Nested path for nested bucket.
     * @param QueryInterface|null $filter       Bucket filter.
     * @param QueryInterface|null $nestedFilter Nested filter for the bucket.
     */
    public function __construct(
        string $name,
        string $field,
        array $metrics = [],
        array $childBuckets = [],
        array $pipelines = [],
        ?string $nestedPath = null,
        ?QueryInterface $filter = null,
        ?QueryInterface $nestedFilter = null
    ) {
        $this->name         = $name;
        $this->field        = $field;
        $this->metrics      = $metrics;
        $this->childBuckets = $childBuckets;
        $this->pipelines    = $pipelines;
        $this->nestedPath   = $nestedPath;
        $this->filter       = $filter;
        $this->nestedFilter = $nestedFilter;
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function isNested()
    {
        return $this->nestedPath !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getNestedPath()
    {
        return $this->nestedPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getNestedFilter()
    {
        return $this->nestedFilter;
    }


    /**
     * {@inheritDoc}
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildBuckets()
    {
        return $this->childBuckets;
    }

    /**
     * {@inheritDoc}
     */
    public function getPipelines()
    {
        return $this->pipelines;
    }
}
