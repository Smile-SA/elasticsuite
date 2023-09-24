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
namespace Smile\ElasticsuiteCore\Api\Search\Request\Container;

/**
 * Search Relevance configuration interface.
 * Used to retrieve relevance configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface RelevanceConfigurationInterface
{
    /**
     * @return string
     */
    public function getMinimumShouldMatch();

    /**
     * @return float
     */
    public function getTieBreaker();

    /**
     * @return int|false
     */
    public function getPhraseMatchBoost();

    /**
     * Retrieve Cutoff Frequency
     *
     * @return float
     */
    public function getCutOffFrequency();

    /**
     * Check if fuzziness is enabled
     *
     * @return bool
     */
    public function isFuzzinessEnabled();

    /**
     * Check if phonetic search is enabled
     *
     * @return bool
     */
    public function isPhoneticSearchEnabled();

    /**
     * Retrieve FuzzinessConfiguration
     *
     * @return \Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface|null
     */
    public function getFuzzinessConfiguration();

    /**
     * Retrieve span match boost value if enabled.
     *
     * @return false|int
     */
    public function getSpanMatchBoost();

    /**
     * Retrieve span number value if enabled.
     *
     * @return false|int
     */
    public function getSpanSize();

    /**
     * Retrieve min_score value if enabled.
     *
     * @return false|int
     */
    public function getMinScore();

    /**
     * Check if the reference collector field should be used instead of the simple 'sku' field
     * when building the exact match filter query.
     *
     * @return bool
     */
    public function isUsingReferenceInExactMatchFilter();

    /**
     * Check if all tokens of the term vectors response should be used.
     *
     * @return bool
     */
    public function isUsingAllTokens();

    /**
     * Check if the term vectors request should also include the reference analyzer collector field.
     *
     * @return bool
     */
    public function isUsingReferenceAnalyzer();

    /**
     * Check if the term vectors request should also include the edge ngram analyzer(s) collector field.
     *
     * @return bool
     */
    public function isUsingEdgeNgramAnalyzer();

    /**
     * If we should use the default analyzer of each field when building the exact match filter query.
     *
     * @return bool
     */
    public function isUsingDefaultAnalyzerInExactMatchFilter();

    /**
     * Are the exact match boosts on whitespace and sortable version of searchable attributes/fields
     * customized.
     *
     * @return bool
     */
    public function areExactMatchSingleTermBoostsCustomized();

    /**
     * Returns the exact match boost for whitespace version of searchable attributes/fields,
     * used instead of the shingle version of attributes/fields when a single term is searched.
     *
     * @return int
     */
    public function getExactMatchSingleTermPhraseMatchBoost();

    /**
     * Returns the exact match boost for sortable version of searchable+sortable attributes/fields.
     *
     * @return int
     */
    public function getExactMatchSingleTermSortableBoost();
}
