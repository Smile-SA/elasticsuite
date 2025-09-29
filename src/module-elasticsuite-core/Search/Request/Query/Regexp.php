<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * ElasticSuite minimal regexp query implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class Regexp implements QueryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $field;

    /**
     * Constructor.
     *
     * @param string  $value Query value ie the regular expression.
     * @param string  $field Query field.
     * @param string  $name  Name of the query.
     * @param integer $boost Query boost.
     */
    public function __construct($value, $field, $name = null, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->field = $field;
        $this->boost = $boost;
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
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
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
        return QueryInterface::TYPE_REGEXP;
    }

    /**
     * Search value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Search field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }
}
