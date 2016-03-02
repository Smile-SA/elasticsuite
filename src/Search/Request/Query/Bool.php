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
 * bool queries request implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Bool implements QueryInterface
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
     * Constructor.
     *
     * @param QueryInterface[] $must    Must clause queries.
     * @param QueryInterface[] $should  Should clause queries.
     * @param QueryInterface[] $mustNot Must not clause queries.
     * @param string           $name    Query name.
     * @param integer          $boost   Query boost.
     */
    public function __construct(
        array $must = [],
        array $should = [],
        array $mustNot = [],
        array $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->must    = $must;
        $this->should  = $should;
        $this->mustNot = $mustNot;
        $this->boost   = $boost;
        $this->name    = $name;
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
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * Must clause queries.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    public function getMust()
    {
        return $this->must;
    }

    /**
     * Should clause queries.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    public function getShould()
    {
        return $this->should;
    }

    /**
     * Must not clause queries.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface[]
     */
    public function getMustNot()
    {
        return $this->mustNot;
    }
}
