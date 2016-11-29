<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query\Fulltext;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;

/**
 * Prepare a fulltext search query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryBuilder
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param QueryFactory $queryFactory Query factory (used to build subqueries.
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Create the fulltext search query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     * @param string                          $spellingType    The type of spellchecked applied.
     * @param float                           $boost           Boost of the created query.
     *
     * @return QueryInterface
     */
    public function create(ContainerConfigurationInterface $containerConfig, $queryText, $spellingType, $boost = 1)
    {
        $query = null;

        $fuzzySpellingTypes = [SpellcheckerInterface::SPELLING_TYPE_FUZZY, SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY];

        if (is_array($queryText)) {
            $queries = [];
            foreach ($queryText as $currentQueryText) {
                $queries[] = $this->create($containerConfig, $currentQueryText, $spellingType);
            }
            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $queries, 'boost' => $boost]);
        } elseif ($spellingType == SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS) {
            $query = $this->getPureStopwordsQuery($containerConfig, $queryText, $boost);
        } elseif (in_array($spellingType, $fuzzySpellingTypes)) {
            $query = $this->getSpellcheckedQuery($containerConfig, $queryText, $spellingType, $boost);
        }

        if ($query === null) {
            $queryParams = [
                'query'  => $this->getWeightedSearchQuery($containerConfig, $queryText),
                'filter' => $this->getCutoffFrequencyQuery($containerConfig, $queryText),
                'boost'  => $boost,
            ];
            $query = $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
        }

        return $query;
    }

    /**
     * Provides a common search query for the searched text.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getCutoffFrequencyQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $relevanceConfig = $containerConfig->getRelevanceConfig();

        $queryParams = [
            'field'              => MappingInterface::DEFAULT_SEARCH_FIELD,
            'queryText'          => $queryText,
            'cutoffFrequency'    => $relevanceConfig->getCutOffFrequency(),
            'minimumShouldMatch' => $relevanceConfig->getMinimumShouldMatch(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_COMMON, $queryParams);
    }

    /**
     * Provides a weighted search query (multi match) using mapping field configuration.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getWeightedSearchQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $relevanceConfig  = $containerConfig->getRelevanceConfig();
        $phraseMatchBoost = $relevanceConfig->getPhraseMatchBoost();
        $defaultSearchField = MappingInterface::DEFAULT_SEARCH_FIELD;
        $searchableCallback = [$this, 'isSearchableFieldCallback'];


        $standardAnalyzer = FieldInterface::ANALYZER_STANDARD;
        $phraseAnalyzer   = FieldInterface::ANALYZER_WHITESPACE;
        if (is_string($queryText) && str_word_count($queryText) > 1) {
            $phraseAnalyzer = FieldInterface::ANALYZER_SHINGLE;
        }

        $searchFields = array_merge(
            $this->getWeightedFields($containerConfig, $standardAnalyzer, $searchableCallback, $defaultSearchField),
            $this->getWeightedFields($containerConfig, $phraseAnalyzer, $searchableCallback, $defaultSearchField, $phraseMatchBoost)
        );

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => 1,
            'cutoffFrequency'    => $relevanceConfig->getCutOffFrequency(),
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }

    /**
     * Build a query when the fulltext search query contains only stopwords.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     * @param float                           $boost           Boost of the created query.
     *
     * @return QueryInterface
     */
    private function getPureStopwordsQuery(ContainerConfigurationInterface $containerConfig, $queryText, $boost)
    {
        $relevanceConfig = $containerConfig->getRelevanceConfig();

        $analyzer = FieldInterface::ANALYZER_WHITESPACE;
        if (is_string($queryText) && str_word_count($queryText) > 1) {
            $analyzer = FieldInterface::ANALYZER_SHINGLE;
        }

        $defaultSearchField = MappingInterface::DEFAULT_SEARCH_FIELD;
        $searchableCallback = [$this, 'isSearchableFieldCallback'];

        $searchFields = $this->getWeightedFields($containerConfig, $analyzer, $searchableCallback, $defaultSearchField);

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => "100%",
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
            'boost'              => $boost,
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }

    /**
     * Spellcheked query building.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     * @param string                          $spellingType    The type of spellchecked applied.
     * @param float                           $boost           Boost of the created query.
     *
     * @return QueryInterface
     */
    private function getSpellcheckedQuery(ContainerConfigurationInterface $containerConfig, $queryText, $spellingType, $boost)
    {
        $query = null;

        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $queryClauses = [];

        if ($relevanceConfig->isFuzzinessEnabled()) {
            $queryClauses[] = $this->getFuzzyQuery($containerConfig, $queryText);
        }

        if ($relevanceConfig->isPhoneticSearchEnabled()) {
            $queryClauses[] = $this->getPhoneticQuery($containerConfig, $queryText);
        }

        if (!empty($queryClauses)) {
            $queryParams = ['should' => $queryClauses, 'boost' => $boost];

            if ($spellingType == SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY) {
                $queryParams['must'] = [$this->getWeightedSearchQuery($containerConfig, $queryText)];
            }

            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
        }

        return $query;
    }

    /**
     * Fuzzy query part.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getFuzzyQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $relevanceConfig    = $containerConfig->getRelevanceConfig();
        $phraseMatchBoost = $relevanceConfig->getPhraseMatchBoost();

        $defaultSearchField = MappingInterface::DEFAULT_SPELLING_FIELD;

        $standardAnalyzer = FieldInterface::ANALYZER_WHITESPACE;
        $phraseAnalyzer   = FieldInterface::ANALYZER_WHITESPACE;
        if (is_string($queryText) && str_word_count($queryText) > 1) {
            $phraseAnalyzer = FieldInterface::ANALYZER_SHINGLE;
        }

        $fuzzyFieldCallback = [$this, 'isFuzzyFieldCallback'];

        $searchFields = array_merge(
            $this->getWeightedFields($containerConfig, $standardAnalyzer, $fuzzyFieldCallback, $defaultSearchField),
            $this->getWeightedFields($containerConfig, $phraseAnalyzer, $fuzzyFieldCallback, $defaultSearchField, $phraseMatchBoost)
        );

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => "100%",
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
            'fuzzinessConfig'    => $relevanceConfig->getFuzzinessConfiguration(),
            'cutoffFrequency'    => $relevanceConfig->getCutoffFrequency(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }

    /**
     * Phonentic query part.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getPhoneticQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $relevanceConfig = $containerConfig->getRelevanceConfig();

        $analyzer           = FieldInterface::ANALYZER_PHONETIC;
        $defaultSearchField = MappingInterface::DEFAULT_SPELLING_FIELD;
        $fuzzyFieldCallback = [$this, 'isFuzzyFieldCallback'];

        $searchFields = $this->getWeightedFields($containerConfig, $analyzer, $fuzzyFieldCallback, $defaultSearchField);

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => "100%",
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
            'cutoffFrequency'    => $relevanceConfig->getCutoffFrequency(),
        ];

        if ($relevanceConfig->getPhoneticConfiguration()->isFuzzinessEnabled()) {
            $queryParams['fuzzinessConfig'] = $relevanceConfig->getPhoneticConfiguration()->getFuzzinessConfiguration();
        }

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }


    /**
     * Build an array of weighted fields to be searched with the ability to apply a filter callback method and a default field.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $analyzer        Target analyzer.
     * @param callable                        $filterCallback  Field filter callback.
     * @param string|null                     $defaultField    Default search field.
     * @param integer                         $boost           Additional boost applied to the fields (multiplicative).
     *
     * @return array
     */
    private function getWeightedFields(
        ContainerConfigurationInterface $containerConfig,
        $analyzer = FieldInterface::ANALYZER_STANDARD,
        $filterCallback = null,
        $defaultField = null,
        $boost = 1
    ) {
        $weightedFields = [];

        if ($defaultField !== null) {
            if ($analyzer != FieldInterface::ANALYZER_STANDARD) {
                $defaultField = sprintf("%s.%s", $defaultField, $analyzer);
            }
            $weightedFields[$defaultField] = 1;
        }

        $fields = $containerConfig->getMapping()->getFields();

        if ($filterCallback) {
            $fields = array_filter($fields, $filterCallback);
        }

        foreach ($fields as $field) {
            $mappingProperty = $field->getMappingProperty($analyzer);

            if ($mappingProperty && ($defaultField === null || $field->getSearchWeight() != 1)) {
                $weightedFields[$mappingProperty] = $field->getSearchWeight() * $boost;
            }
        }

        return $weightedFields;
    }

    /**
     * Indicates if a field is searchable.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param FieldInterface $field Mapping field.
     *
     * @return boolean
     */
    private function isSearchableFieldCallback(FieldInterface $field)
    {
        return $field->isSearchable() && $field->getType() == FieldInterface::FIELD_TYPE_STRING;
    }

    /**
     * Indicates if a field is used in fuzzy search.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param FieldInterface $field Mapping field.
     *
     * @return boolean
     */
    private function isFuzzyFieldCallback(FieldInterface $field)
    {
        return $field->isSearchable() && $field->isUsedInSpellcheck();
    }
}
