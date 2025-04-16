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

namespace Smile\ElasticsuiteCore\Search\Request\Query\Fulltext;

use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Reader\Container;
use Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;

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
     *
     * @var FieldFilterInterface[]
     */
    private $fieldFilters;

    /**
     * Constructor.
     *
     * @param QueryFactory           $queryFactory Query factory (used to build subqueries.
     * @param FieldFilterInterface[] $fieldFilters Field filters models.
     */
    public function __construct(QueryFactory $queryFactory, array $fieldFilters = [])
    {
        $this->queryFactory = $queryFactory;
        $this->fieldFilters = $fieldFilters;
    }

    /**
     * Create the fulltext search query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     * @param string                          $spellingType    The type of spellchecked applied.
     * @param float                           $boost           Boost of the created query.
     * @param int                             $depth           Call depth of the create method. Can be used to avoid/prevent cycles.
     *
     * @return QueryInterface
     */
    public function create(ContainerConfigurationInterface $containerConfig, $queryText, $spellingType, $boost = 1, $depth = 0)
    {
        $query = null;

        $fuzzySpellingTypes = [SpellcheckerInterface::SPELLING_TYPE_FUZZY, SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY];

        if (is_array($queryText)) {
            $queries = [];
            foreach ($queryText as $currentQueryText) {
                $queries[] = $this->create($containerConfig, $currentQueryText, $spellingType, $boost, $depth + 1);
            }
            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $queries, 'boost' => $boost]);
        } elseif ($spellingType == SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS) {
            $query = $this->getPureStopwordsQuery($containerConfig, $queryText, $boost);
            $query->setName('PURE_STOPWORDS');
        } elseif (in_array($spellingType, $fuzzySpellingTypes)) {
            $query = $this->getSpellcheckedQuery($containerConfig, $queryText, $spellingType, $boost);
            if ($query !== null) {
                $query->setName('SPELLCHECK');
            }
        }

        if ($query === null) {
            $queryParams = [
                'query'  => $this->getWeightedSearchQuery($containerConfig, $queryText),
                'filter' => $this->getCutoffFrequencyQuery($containerConfig, $queryText),
                'boost'  => $boost,
            ];
            $query = $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
            $query->setName('EXACT');

            $relevanceConfig = $containerConfig->getRelevanceConfig();
            if ($relevanceConfig->getSpanMatchBoost()) {
                $spanQuery = $this->getSpanQuery($containerConfig, $queryText, $relevanceConfig->getSpanMatchBoost());
                if ($spanQuery !== null) {
                    $spanQuery->setName('SPAN');
                    $queryParams = [
                        'must'      => [$query],
                        'should'    => [$spanQuery],
                        'minimumShouldMatch' => 0,
                    ];
                    $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
                }
            }
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
        $fields          = array_fill_keys([MappingInterface::DEFAULT_SEARCH_FIELD], 1);

        if ($containerConfig->getRelevanceConfig()->isUsingDefaultAnalyzerInExactMatchFilter()) {
            $nonStandardSearchableFieldFilter = $this->fieldFilters['nonStandardSearchableFieldFilter'];

            $fields = $fields + $this->getWeightedFields(
                $containerConfig,
                null,
                $nonStandardSearchableFieldFilter,
                MappingInterface::DEFAULT_SEARCH_FIELD
            );
        }

        if ($containerConfig->getRelevanceConfig()->isUsingReferenceInExactMatchFilter()) {
            $fields += array_fill_keys(
                [MappingInterface::DEFAULT_SEARCH_FIELD, MappingInterface::DEFAULT_REFERENCE_FIELD . ".reference"],
                1
            );
        }

        $queryParams = [
            'fields'             => array_fill_keys(array_keys($fields), 1),
            'queryText'          => $queryText,
            'cutoffFrequency'    => $relevanceConfig->getCutOffFrequency(),
            'minimumShouldMatch' => $relevanceConfig->getMinimumShouldMatch(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
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
        $relevanceConfig       = $containerConfig->getRelevanceConfig();
        $phraseMatchBoost      = $relevanceConfig->getPhraseMatchBoost();
        $defaultSearchField    = MappingInterface::DEFAULT_SEARCH_FIELD;
        $searchableFieldFilter = $this->fieldFilters['searchableFieldFilter'];
        $sortableAnalyzer      = FieldInterface::ANALYZER_SORTABLE;
        $phraseAnalyzer        = FieldInterface::ANALYZER_WHITESPACE;
        $sortableMatchBoost    = 2 * $phraseMatchBoost;

        if (is_string($queryText) && str_word_count($queryText) > 1) {
            $phraseAnalyzer = FieldInterface::ANALYZER_SHINGLE;
        } elseif ($relevanceConfig->areExactMatchSingleTermBoostsCustomized()) {
            $phraseMatchBoost = $relevanceConfig->getExactMatchSingleTermPhraseMatchBoost();
            $sortableMatchBoost = $relevanceConfig->getExactMatchSingleTermSortableBoost();
        }

        $searchFields = array_merge(
            $this->getWeightedFields($containerConfig, null, $searchableFieldFilter, $defaultSearchField),
            $this->getWeightedFields($containerConfig, $phraseAnalyzer, $searchableFieldFilter, $defaultSearchField, $phraseMatchBoost),
            $this->getWeightedFields($containerConfig, $sortableAnalyzer, $searchableFieldFilter, null, $sortableMatchBoost)
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

        $defaultSearchField    = MappingInterface::DEFAULT_SEARCH_FIELD;
        $searchableFieldFilter = $this->fieldFilters['searchableFieldFilter'];

        $searchFields = $this->getWeightedFields($containerConfig, $analyzer, $searchableFieldFilter, $defaultSearchField);

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
     * Spellchecked query building.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     * @param string                          $spellingType    The type of spellchecked applied.
     * @param float                           $boost           Boost of the created query.
     *
     * @return QueryInterface|null
     */
    private function getSpellcheckedQuery(ContainerConfigurationInterface $containerConfig, $queryText, $spellingType, $boost)
    {
        $query = null;

        $relevanceConfig = $containerConfig->getRelevanceConfig();
        $queryClauses = [];

        if ($relevanceConfig->isFuzzinessEnabled()) {
            $queryClauses[] = $this->getFuzzyQuery($containerConfig, $queryText)->setName('FUZZY');
        }

        if ($relevanceConfig->isPhoneticSearchEnabled()) {
            $queryClauses[] = $this->getPhoneticQuery($containerConfig, $queryText)->setName('PHONETIC');
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

        $fuzzyFieldFilter = $this->fieldFilters['fuzzyFieldFilter'];
        $nonStandardFuzzyFieldFilter = $this->fieldFilters['nonStandardFuzzyFieldFilter'];

        $searchFields = array_merge(
            $this->getWeightedFields($containerConfig, $standardAnalyzer, $fuzzyFieldFilter, $defaultSearchField),
            $this->getWeightedFields($containerConfig, $phraseAnalyzer, $fuzzyFieldFilter, $defaultSearchField, $phraseMatchBoost),
            // Allow fuzzy query to contain fields using for fuzzy search with their default analyzer.
            // Same logic as defined in getWeightedSearchQuery().
            // This will automatically include sku.reference and any other fields having defaultSearchAnalyzer.
            $this->getWeightedFields($containerConfig, null, $nonStandardFuzzyFieldFilter, $defaultSearchField),
        );

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => $relevanceConfig->getMinimumShouldMatch(),
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
            'fuzzinessConfig'    => $relevanceConfig->getFuzzinessConfiguration(),
            'cutoffFrequency'    => $relevanceConfig->getCutoffFrequency(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }

    /**
     * Phonetic query part.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getPhoneticQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $relevanceConfig    = $containerConfig->getRelevanceConfig();
        $analyzer           = FieldInterface::ANALYZER_PHONETIC;
        $defaultSearchField = MappingInterface::DEFAULT_SPELLING_FIELD;
        $fuzzyFieldFilter   = $this->fieldFilters['fuzzyFieldFilter'];
        $minimumShouldMatch = $relevanceConfig->getMinimumShouldMatch();
        if ($relevanceConfig->getFuzzinessConfiguration() instanceof FuzzinessConfigurationInterface) {
            $minimumShouldMatch = $relevanceConfig->getFuzzinessConfiguration()->getMinimumShouldMatch();
        }

        $searchFields = $this->getWeightedFields($containerConfig, $analyzer, $fuzzyFieldFilter, $defaultSearchField);

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => $minimumShouldMatch,
            'tieBreaker'         => $relevanceConfig->getTieBreaker(),
            'cutoffFrequency'    => $relevanceConfig->getCutoffFrequency(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }


    /**
     * Build an array of weighted fields to be searched with the ability to apply a filter callback method and a default field.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string|null                     $analyzer        Target analyzer.
     * @param FieldFilterInterface|null       $fieldFilter     Field filter.
     * @param string|null                     $defaultField    Default search field.
     * @param integer|null                    $boost           Additional boost applied to the fields (multiplicative).
     *
     * @return array
     */
    private function getWeightedFields(
        ContainerConfigurationInterface $containerConfig,
        ?string $analyzer = null,
        ?FieldFilterInterface $fieldFilter = null,
        ?string $defaultField = null,
        ?int $boost = 1
    ) {

        $mapping = $containerConfig->getMapping();

        return $mapping->getWeightedSearchProperties($analyzer, $defaultField, $boost, $fieldFilter);
    }

    /**
     * Build a span query to raise score of fields beginning by the query text.
     *
     * @param ContainerConfigurationInterface $containerConfig The container configuration
     * @param string                          $queryText       The query text
     * @param int                             $boost           The boost applied to the span query
     *
     * @return QueryInterface|null
     */
    private function getSpanQuery(ContainerConfigurationInterface $containerConfig, $queryText, $boost)
    {
        $query = null;
        $terms = explode(' ', $queryText);

        $relevanceConfig  = $containerConfig->getRelevanceConfig();
        $spanSize         = $relevanceConfig->getSpanSize();

        if ((int) $spanSize === 0) {
            return $query;
        }

        $terms            = array_slice($terms, 0, $spanSize);
        $wordCount        = count($terms);
        $spanFieldsFilter = $this->fieldFilters['spannableFieldFilter'];
        $spanFields       = $containerConfig->getMapping()->getFields();
        $spanFields       = array_filter($spanFields, [$spanFieldsFilter, 'filterField']);
        $spanQueryParams  = ['boost' => $boost, 'end' => $wordCount];
        $spanQueryType    = SpanQueryInterface::TYPE_SPAN_FIRST;

        if (count($spanFields) > 0) {
            $queries = [];
            foreach ($spanFields as $field) {
                $clauses = [];
                foreach ($terms as $term) {
                    $clauses[] = $this->queryFactory->create(
                        SpanQueryInterface::TYPE_SPAN_TERM,
                        [
                            'field' => $field->getMappingProperty(FieldInterface::ANALYZER_WHITESPACE) ?? $field->getName(),
                            'value' => strtolower($term),
                        ]
                    );
                }

                $spanQueryParams['match'] = $this->queryFactory->create(
                    SpanQueryInterface::TYPE_SPAN_NEAR,
                    [
                        'clauses' => $clauses,
                        'slop'    => 0,
                        'inOrder' => true,
                    ]
                );

                $queries[] = $this->queryFactory->create($spanQueryType, $spanQueryParams);
            }

            $query = current($queries);
            if (count($queries) > 1) {
                $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $queries]);
            }
        }

        return $query;
    }
}
