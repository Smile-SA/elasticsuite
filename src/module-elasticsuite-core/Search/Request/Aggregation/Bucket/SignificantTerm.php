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
 * Significant term bucket implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SignificantTerm extends AbstractBucket
{
    const ALGORITHM_GND = 'gnd';

    const ALGORITHM_CHI_SQUARE = 'chi_sqare';

    const ALGORITHM_JLH = 'jlh';

    const ALGORITHM_PERCENTAGE = 'percentage';

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $minDocCount;

    /**
     * @var string
     */
    private $algorithm;

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
     * @param integer             $minDocCount  Min doc count.
     * @param string              $algotithm    Algorithm used
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
        int $minDocCount = 5,
        string $algotithm = self::ALGORITHM_GND
    ) {
        parent::__construct($name, $field, $metrics, $childBuckets, $pipelines, $nestedPath, $filter, $nestedFilter);

        $this->minDocCount = $minDocCount;
        $this->algorithm   = $algotithm;
        $this->size        = $size > 0 && $size < self::MAX_BUCKET_SIZE ? $size : self::MAX_BUCKET_SIZE;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return BucketInterface::TYPE_SIGNIFICANT_TERM;
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
     * Min doc count for a value to be displayed.
     *
     * @return integer
     */
    public function getMinDocCount()
    {
        return $this->minDocCount;
    }

    /**
     * Algorithm used for the value selection.
     *
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }
}
