<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

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
     * Constructor.
     *
     * @param string         $name         Bucket name.
     * @param string         $field        Bucket field.
     * @param Metric[]       $metrics      Bucket metrics.
     * @param string         $nestedPath   Nested path for nested bucket.
     * @param QueryInterface $filter       Bucket filter.
     * @param QueryInterface $nestedFilter Nested filter for the bucket.
     * @param integer        $interval     Histogram interval.
     * @param integer        $minDocCount  Histogram min doc count.
     */
    public function __construct(
        $name,
        $field,
        array $metrics,
        $nestedPath = null,
        QueryInterface $filter = null,
        QueryInterface $nestedFilter = null,
        $interval = 1,
        $minDocCount = 0
    ) {
        parent::__construct($name, $field, $metrics, $nestedPath, $filter, $nestedFilter);
        $this->interval    = $interval;
        $this->minDocCount = $minDocCount;
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
}
