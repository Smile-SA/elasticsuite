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

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * ElasticSuite range query implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Range implements QueryInterface
{
    /**
     * @var integer
     */
    private $boost;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $field;

    /**
     * @var integer
     */
    private $from;

    /**
     * @var integer
     */
    private $to;

    /**
     * Constructor.
     *
     * @param string  $name  Query name.
     * @param string  $field Query field.
     * @param string  $from  Lower bound of the range filter.
     * @param integer $to    Highter bound of the range filter.
     * @param integer $boost Query boost.
     */
    public function __construct($name, $field, $from, $to, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->boost = $boost;
        $this->field = $field;
        $this->from  = $from;
        $this->to    = $to;
    }

    /**
     * {@inheritDoc}
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_RANGE;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Query field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Lower bound of the range filter.
     *
     * @return integer
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Highter bound of the range filter.
     *
     * @return integer
     */
    public function getTo()
    {
        return $this->to;
    }
}
