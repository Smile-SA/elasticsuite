<?php
/**
 * DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
* versions in the future.
*
* @category  Smile
* @package   Smile_ElasticSuiteCore
* @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
* @copyright 2016 Smile
* @license   Open Software License ("OSL") v. 3.0
*/

namespace Smile\ElasticSuiteCore\Search\Request\Aggregation\Bucket;

use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Aggregation\TermBucket;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Term Bucket implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Term extends AbstractBucket
{
    /**
     * @var integer
     */
    private $size;

    /**
     * Constructor.
     *
     * @param string         $name       Bucket name.
     * @param string         $field      Bucket field.
     * @param Metric[]       $metrics    Bucket metrics.
     * @param string         $nestedPath Nested path for nested bucket.
     * @param QueryInterface $filter     Bucket filter.
     * @param integer        $size       Bucket size.
     */
    public function __construct(
        $name,
        $field,
        array $metrics,
        $nestedPath = null,
        QueryInterface $filter = null,
        $size = 0
    ) {
        parent::__construct($name, $field, $metrics, $nestedPath, $filter);
        $this->size = $size;
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
}
