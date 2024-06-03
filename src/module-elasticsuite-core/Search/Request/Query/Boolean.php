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
 * bool queries request implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Boolean implements QueryInterface
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
     * @var QueryInterface[]
     */
    private $must;

    /**
     * @var QueryInterface[]
     */
    private $should;

    /**
     * @var QueryInterface[]
     */
    private $mustNot;

    /**
     * @var integer
     */
    private $minimumShouldMatch;

    /**
     * @var boolean
     */
    private $cached;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param QueryInterface[] $must               Must clause queries.
     * @param QueryInterface[] $should             Should clause queries.
     * @param QueryInterface[] $mustNot            Must not clause queries.
     * @param integer          $minimumShouldMatch Minimum should match query clause.
     * @param string           $name               Query name.
     * @param integer          $boost              Query boost.
     * @param boolean          $cached             Should the query be cached or not.
     */
    public function __construct(
        array $must = [],
        array $should = [],
        array $mustNot = [],
        $minimumShouldMatch = 1,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE,
        $cached = false
    ) {
        $this->must               = $must;
        $this->should             = $should;
        $this->mustNot            = $mustNot;
        $this->boost              = $boost;
        $this->name               = $name;
        $this->minimumShouldMatch = $minimumShouldMatch;
        $this->cached             = $cached;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_BOOL;
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
     * Must clause queries.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getMust()
    {
        return $this->must;
    }

    /**
     * Should clause queries.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getShould()
    {
        return $this->should;
    }

    /**
     * Must not clause queries.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    public function getMustNot()
    {
        return $this->mustNot;
    }

    /**
     * Minimum should match query clause.
     *
     * @return integer
     */
    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }

    /**
     * Indicates if the bool query needs to be cached or not.
     *
     * @return boolean
     */
    public function isCached()
    {
        return $this->cached;
    }
}
