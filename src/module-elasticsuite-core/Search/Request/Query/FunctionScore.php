<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Query negation definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class FunctionScore implements QueryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * @var string
     */
    private $scoreMode;

    /**
     * @var string
     */
    private $boostMode;

    /**
     * @var array
     */
    private $functions;

    /**
     * Score mode functions.
     */
    const SCORE_MODE_MULTIPLY = 'multiply';
    const SCORE_MODE_SUM      = 'sum';
    const SCORE_MODE_AVG      = 'avg';
    const SCORE_MODE_FIRST    = 'first';
    const SCORE_MODE_MAX      = 'max';
    const SCORE_MODE_MIN      = 'min';

    /**
     * Boost mode functions.
     */
    const BOOST_MODE_MULTIPLY = 'multiply';
    const BOOST_MODE_REPLACE  = 'replace';
    const BOOST_MODE_SUM      = 'sum';
    const BOOST_MODE_AVG      = 'avg';
    const BOOST_MODE_MAX      = 'max';
    const BOOST_MODE_MIN      = 'min';

    /**
     * Functions score list.
     */
    const FUNCTION_SCORE_SCRIPT_SCORE       = 'script_score';
    const FUNCTION_SCORE_WEIGHT             = 'weight';
    const FUNCTION_SCORE_RANDOM_SCORE       = 'random_score';
    const FUNCTION_SCORE_FIELD_VALUE_FACTOR = 'field_value_factor';

    /**
     * Constructor.
     * @param \Magento\Framework\Search\Request\QueryInterface $query     Negated query.
     * @param array                                            $functions Function score.
     * @param string                                           $name      Query name.
     * @param string                                           $scoreMode Score mode.
     * @param string                                           $boostMode Boost mode.
     */
    public function __construct(
        \Magento\Framework\Search\Request\QueryInterface $query,
        $functions = [],
        $name = null,
        $scoreMode = self::SCORE_MODE_SUM,
        $boostMode = self::BOOST_MODE_SUM
    ) {
        $this->name      = $name;
        $this->query     = $query;
        $this->scoreMode = $scoreMode;
        $this->boostMode = $boostMode;
        $this->functions = $functions;
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
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_FUNCTIONSCORE;
    }

    /**
     * Returns score mode.
     *
     * @return string
     */
    public function getScoreMode()
    {
        return $this->scoreMode;
    }

    /**
     * Returns boost mode.
     *
     * @return string
     */
    public function getBoostMode()
    {
        return $this->boostMode;
    }

    /**
     * Return function score base query.
     *
     * @return \Magento\Framework\Search\Request\QueryInterface|QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns function score.
     *
     * @return array
     */
    public function getFunctions()
    {
        return $this->functions;
    }
}
