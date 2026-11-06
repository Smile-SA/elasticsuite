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

use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Multi match search request query implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MultiMatch implements QueryInterface
{
    /**
     * @var string
     */
    const DEFAULT_MINIMUM_SHOULD_MATCH = "1";

    /**
     * @var integer
     */
    const DEFAULT_TIE_BREAKER = 1;

    /**
     * @var string
     */
    const DEFAULT_MATCH_TYPE = "best_fields";

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
    private $queryText;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     * @var integer
     */
    private $tieBreaker;

    /**
     * @var null|FuzzinessConfigurationInterface
     */
    private $fuzzinessConfig;

    /**
     * @var float
     */
    private $cutoffFrequency;

    /**
     * @var string
     */
    private $matchType;

    /**
     * @param string                               $queryText          Matched text.
     * @param array                                $fields             Query fields as key with their weigth as values.
     * @param string                               $minimumShouldMatch Minimum should match for the match query.
     * @param integer                              $tieBreaker         Tie breaker for the multi_match query.
     * @param string                               $name               Query name.
     * @param int                                  $boost              Query boost.
     * @param FuzzinessConfigurationInterface|null $fuzzinessConfig    The fuzziness Configuration
     * @param float                                $cutoffFrequency    Cutoff frequency.
     * @param string                               $matchType          The match type.
     */
    public function __construct(
        $queryText,
        array $fields,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $tieBreaker = self::DEFAULT_TIE_BREAKER,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE,
        ?FuzzinessConfigurationInterface $fuzzinessConfig = null,
        $cutoffFrequency = null,
        $matchType = self::DEFAULT_MATCH_TYPE
    ) {
        $this->name               = $name;
        $this->queryText          = $queryText;
        $this->fields             = $fields;
        $this->minimumShouldMatch = $minimumShouldMatch;
        $this->tieBreaker         = $tieBreaker;
        $this->boost              = $boost;
        $this->fuzzinessConfig    = $fuzzinessConfig;
        $this->cutoffFrequency    = $cutoffFrequency;
        $this->matchType          = $matchType;
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
        return QueryInterface::TYPE_MULTIMATCH;
    }

    /**
     * Query match text.
     *
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * Query fields (weighted).
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Minimum should match for the match query.
     *
     * @return string
     */
    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }

    /**
     * Tie breaker for the multi_match query.
     *
     * @return float
     */
    public function getTieBreaker()
    {
        return $this->tieBreaker;
    }

    /**
     * Retrieve Fuzziness Configuration if any
     *
     * @return null|FuzzinessConfigurationInterface
     */
    public function getFuzzinessConfiguration()
    {
        return $this->fuzzinessConfig;
    }

    /**
     * Query cutoff frequency.
     *
     * @deprecated on multi_match since ES 8.
     *
     * @return float
     */
    public function getCutoffFrequency()
    {
        return $this->cutoffFrequency;
    }

    /**
     * @return string
     */
    public function getMatchType()
    {
        return $this->matchType;
    }
}
