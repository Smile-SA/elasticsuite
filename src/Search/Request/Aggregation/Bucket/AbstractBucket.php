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
use Magento\Framework\Search\Request\Aggregation\Metric;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Abstract bucket implementation.
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
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
     * @var Metric[]
     */
    private $metrics;

    /**
     * @var string
     */
    private $nestedPath;

    /**
     * @var QueryInterface|null
     */
    private $filter;

    /**
     * Constructor.
     *
     * @param string         $name       Bucket name.
     * @param string         $field      Bucket field.
     * @param Metric[]       $metrics    Bucket metrics.
     * @param string         $nestedPath Nested path for nested bucket.
     * @param QueryInterface $filter     Bucket filter.
     */
    public function __construct($name, $field, array $metrics, $nestedPath = null, QueryInterface $filter = null)
    {
        $this->name       = $name;
        $this->field      = $field;
        $this->metrics    = $metrics;
        $this->nestedPath = $nestedPath;
        $this->filter     = $filter;
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
        return $this->nestedPath != null;
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
    public function getFilter()
    {
        return $this->filter;
    }
}
