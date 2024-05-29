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

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * ElasticSuite range query implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @var array
     */
    private $bounds;

    /**
     * Constructor.
     *
     * @param string  $field  Query field.
     * @param array   $bounds Range filter bounds (authorized entries : gt, lt, lte, gte).
     * @param string  $name   Query name.
     * @param integer $boost  Query boost.
     */
    public function __construct($field, array $bounds = [], $name = null, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->boost = $boost;
        $this->field = $field;
        $this->bounds = $bounds;
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
     * {@inheritDoc}
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
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
     * Range filter bounds.
     *
     * @return array
     */
    public function getBounds()
    {
        return $this->bounds;
    }
}
