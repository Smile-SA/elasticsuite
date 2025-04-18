<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;

/**
 * Relevance Configuration object
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RelevanceConfig implements RelevanceConfigurationInterface
{
    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     * @var float
     */
    private $tieBreaker;

    /**
     * @var integer|null
     */
    private $phraseMatchBoost;

    /**
     * @var float
     */
    private $cutOffFrequency;

    /**
     * @var FuzzinessConfigurationInterface
     */
    private $fuzzinessConfiguration;

    /**
     * @var boolean
     */
    private $enablePhoneticSearch;

    /**
     * @var integer|null
     */
    private $spanMatchBoost;

    /**
     * @var integer|null
     */
    private $spanSize;

    /**
     * @var integer|null
     */
    private $minScore;

    /**
     * @var boolean
     */
    private $useReferenceInExactMatchFilter;

    /**
     * @var boolean
     */
    private $useDefaultAnalyzerInExactMatchFilter;

    /**
     * @var boolean
     */
    private $useAllTokens;

    /**
     * @var boolean
     */
    private $useReferenceAnalyzer;

    /**
     * @var boolean
     */
    private $useEdgeNgramAnalyzer;

    /**
     * @var boolean
     */
    private $exactMatchSingleTermBoostsCustomized;

    /**
     * @var integer|null
     */
    private $exactMatchSingleTermPhraseMatchBoost;

    /**
     * @var integer|null
     */
    private $exactMatchSingleTermSortableBoost;

    /**
     * RelevanceConfiguration constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string                               $minimumShouldMatch                   Minimum should match clause of the text query.
     * @param float                                $tieBreaker                           Tie breaker for multimatch queries.
     * @param int|null                             $phraseMatchBoost                     The Phrase match boost value, or null if not
     *                                                                                   enabled
     * @param float                                $cutOffFrequency                      The cutoff Frequency value
     * @param FuzzinessConfigurationInterface|null $fuzziness                            The fuzziness Configuration, or null
     * @param boolean                              $enablePhoneticSearch                 The phonetic Configuration, or null
     * @param int|null                             $spanMatchBoost                       The Span match boost value, or null if not
     *                                                                                   enabled
     * @param int|null                             $spanSize                             The number of terms to match in span queries
     * @param int|null                             $minScore                             The Min Score value, or null if not enabled
     * @param boolean                              $useReferenceInExactMatchFilter       Whether to use the reference collector field
     *                                                                                   instead of 'sku' field in the exact match filter
     * @param boolean                              $useDefaultAnalyzerInExactMatchFilter Whether to use 'field' or 'field.default_analyzer'
     *                                                                                   in the exact match filter query
     * @param boolean                              $useAllTokens                         Whether to take into account all term vector tokens
     * @param boolean                              $useReferenceAnalyzer                 Whether to include the collector field associated
     *                                                                                   with the reference analyzer in term vectors request
     * @param boolean                              $useEdgeNgramAnalyzer                 Whether to include the collector field associated
     *                                                                                   with the edge ngram analyzer(s) in the term vectors
     *                                                                                   request
     * @param boolean                              $exactMatchSingleTermBoostsCustomized Are the exact match boost values on whitespace
     *                                                                                   and sortable fields customized.
     * @param int|null                             $exactMatchSingleTermPhraseMatchBoost The whitespace boost value for exact match,
     *                                                                                   or null if the default (phrase match boost value)
     *                                                                                   should apply
     * @param int|null                             $exactMatchSingleTermSortableBoost    The sortable boost value for exact match,
     *                                                                                   or null if the default (twice the phrase match
     *                                                                                   boost value) should apply
     */
    public function __construct(
        $minimumShouldMatch,
        $tieBreaker,
        $phraseMatchBoost,
        $cutOffFrequency,
        ?FuzzinessConfigurationInterface $fuzziness = null,
        $enablePhoneticSearch = false,
        $spanMatchBoost = null,
        $spanSize = null,
        $minScore = null,
        $useReferenceInExactMatchFilter = false,
        $useDefaultAnalyzerInExactMatchFilter = false,
        $useAllTokens = false,
        $useReferenceAnalyzer = false,
        $useEdgeNgramAnalyzer = false,
        $exactMatchSingleTermBoostsCustomized = false,
        $exactMatchSingleTermPhraseMatchBoost = null,
        $exactMatchSingleTermSortableBoost = null
    ) {
        $this->minimumShouldMatch     = $minimumShouldMatch;
        $this->tieBreaker             = $tieBreaker;
        $this->phraseMatchBoost       = $phraseMatchBoost;
        $this->cutOffFrequency        = $cutOffFrequency;
        $this->fuzzinessConfiguration = $fuzziness;
        $this->enablePhoneticSearch   = $enablePhoneticSearch;
        $this->spanMatchBoost         = $spanMatchBoost;
        $this->spanSize               = $spanSize;
        $this->minScore               = $minScore;
        $this->useReferenceInExactMatchFilter   = $useReferenceInExactMatchFilter;
        $this->useAllTokens           = $useAllTokens;
        $this->useReferenceAnalyzer   = $useReferenceAnalyzer;
        $this->useEdgeNgramAnalyzer   = $useEdgeNgramAnalyzer;
        $this->useDefaultAnalyzerInExactMatchFilter = $useDefaultAnalyzerInExactMatchFilter;
        $this->exactMatchSingleTermBoostsCustomized = $exactMatchSingleTermBoostsCustomized;
        $this->exactMatchSingleTermPhraseMatchBoost = $exactMatchSingleTermPhraseMatchBoost;
        $this->exactMatchSingleTermSortableBoost = $exactMatchSingleTermSortableBoost;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }

    /**
     * {@inheritDoc}
     */
    public function getTieBreaker()
    {
        return $this->tieBreaker;
    }

    /**
     * @return int|null
     */
    public function getPhraseMatchBoost()
    {
        return (int) $this->phraseMatchBoost;
    }

    /**
     * @return float
     */
    public function getCutOffFrequency()
    {
        return (float) $this->cutOffFrequency;
    }

    /**
     * Retrieve FuzzinessConfiguration
     *
     * @return FuzzinessConfigurationInterface|null
     */
    public function getFuzzinessConfiguration()
    {
        return $this->fuzzinessConfiguration;
    }

    /**
     * Check if fuzziness is enabled
     *
     * @return boolean
     */
    public function isFuzzinessEnabled()
    {
        return (bool) $this->fuzzinessConfiguration;
    }

    /**
     * Check if phonetic search is enabled
     *
     * @return boolean
     */
    public function isPhoneticSearchEnabled()
    {
        return (bool) $this->enablePhoneticSearch;
    }

    /**
     * {@inheritDoc}
     */
    public function getSpanMatchBoost()
    {
        return (int) $this->spanMatchBoost;
    }

    /**
     * {@inheritDoc}
     */
    public function getSpanSize()
    {
        return (int) $this->spanSize;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinScore()
    {
        return (int) $this->minScore;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingReferenceInExactMatchFilter()
    {
        return (bool) $this->useReferenceInExactMatchFilter;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingDefaultAnalyzerInExactMatchFilter()
    {
        return (bool) $this->useDefaultAnalyzerInExactMatchFilter;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingAllTokens()
    {
        return (bool) $this->useAllTokens;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingReferenceAnalyzer()
    {
        return (bool) $this->useReferenceAnalyzer;
    }

    /**
     * {@inheritDoc}
     */
    public function isUsingEdgeNgramAnalyzer()
    {
        return (bool) $this->useEdgeNgramAnalyzer;
    }

    /**
     * {@inheritDoc}
     */
    public function areExactMatchSingleTermBoostsCustomized()
    {
        return (bool) $this->exactMatchSingleTermBoostsCustomized;
    }

    /**
     * {@inheritDoc}
     */
    public function getExactMatchSingleTermPhraseMatchBoost()
    {
        return (int) $this->exactMatchSingleTermPhraseMatchBoost;
    }

    /**
     * {@inheritDoc}
     */
    public function getExactMatchSingleTermSortableBoost()
    {
        return (int) $this->exactMatchSingleTermSortableBoost;
    }
}
