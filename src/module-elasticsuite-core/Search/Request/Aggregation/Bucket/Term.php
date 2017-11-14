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
    const MAX_BUCKET_SIZE = 100000;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var string
     */
    private $sortOrder;

    /**
     * Constructor.
     *
     * @param string         $name         Bucket name.
     * @param string         $field        Bucket field.
     * @param Metric[]       $metrics      Bucket metrics.
     * @param string         $nestedPath   Nested path for nested bucket.
     * @param QueryInterface $filter       Bucket filter.
     * @param QueryInterface $nestedFilter Nested filter for the bucket.
     * @param integer        $size         Bucket size.
     * @param string         $sortOrder    Bucket sort order.
     */
    public function __construct(
        $name,
        $field,
        array $metrics,
        $nestedPath = null,
        QueryInterface $filter = null,
        QueryInterface $nestedFilter = null,
        $size = 0,
        $sortOrder = BucketInterface::SORT_ORDER_MANUAL
    ) {
        parent::__construct($name, $field, $metrics, $nestedPath, $filter, $nestedFilter);

        $this->size      = $size > 0 && $size < self::MAX_BUCKET_SIZE ? $size : self::MAX_BUCKET_SIZE;
        $this->sortOrder = $sortOrder;
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
}
