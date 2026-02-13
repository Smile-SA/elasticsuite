<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Plugin;

use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Model\Index;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Helper\Text as TextHelper;

/**
 * Plugin that handle query rewriting (synonym substitution) during fulltext query building phase.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryRewrite
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var ThesaurusConfigFactory
     */
    private $thesaurusConfigFactory;

    /**
     * @var Index
     */
    private $index;

    /**
     * @var Text
     */
    private $textHelper;

    /**
     * @var array
     */
    private $rewritesCache = [];

    /**
     * Constructor.
     *
     * @param QueryFactory           $queryFactory           Search request query factory.
     * @param ThesaurusConfigFactory $thesaurusConfigFactory Thesaurus configuration factory.
     * @param Index                  $index                  Synonym index.
     * @param TextHelper             $textHelper             Text helper.
     */
    public function __construct(
        QueryFactory $queryFactory,
        ThesaurusConfigFactory $thesaurusConfigFactory,
        Index $index,
        TextHelper $textHelper
    ) {
        $this->queryFactory           = $queryFactory;
        $this->thesaurusConfigFactory = $thesaurusConfigFactory;
        $this->index                  = $index;
        $this->textHelper             = $textHelper;
    }

    /**
     * Rewrite the query.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryBuilder                    $subject         Original query builder.
     * @param \Closure                        $proceed         Original create func.
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string|array                    $queryText       Current query text.
     * @param string                          $spellingType    Spelling type of the query.
     * @param float                           $boost           Original query boost.
     * @param int                             $depth           Call depth of the create method. Can be used to avoid/prevent cycles.
     *
     * @return QueryInterface
     */
    public function aroundCreate(
        QueryBuilder $subject,
        \Closure $proceed,
        ContainerConfigurationInterface $containerConfig,
        $queryText,
        $spellingType,
        $boost = 1,
        $depth = 0
    ) {
        $storeId         = $containerConfig->getStoreId();
        $requestName     = $containerConfig->getName();
        $rewriteCacheKey = $requestName . '|' . $storeId . '|' . $depth . '|' . md5(json_encode($queryText));

        if (!isset($this->rewritesCache[$rewriteCacheKey])) {
            $rewrites = [];
            /*
             * Prevents multiple and excessive rewriting when calling the fulltext query builder 'create' method
             * with an array of query text
             * - ALL queries will be rewritten here on the first pass in this plugin
             * - but no longer on the consecutive "atomic" calls from the 'create' method to itself
             * Also prevents rewriting a query text that has been provided by the rewriting process.
             */
            if ($depth === 0) {
                $rewrites = $this->getWeightedRewrites($queryText, $containerConfig, $boost);
            }
            $originalSpellingType = $spellingType;
            // Set base query as SPELLING_TYPE_EXACT if synonyms/expansions are found.
            // This is to prevent possible fuzzy matches on that original query's terms and doing so, prioritize the rewritten queries.
            $spellingType = empty($rewrites) ? $spellingType : SpellcheckerInterface::SPELLING_TYPE_EXACT;
            $query        = $proceed($containerConfig, $queryText, $spellingType, $boost, $depth);

            if (!empty($rewrites)) {
                $synonymQueries           = [$query];
                // Do not enforce SPELLING_TYPE_EXACT systematically for alternative queries.
                $synonymQueriesSpellcheck = $this->getRewritesSpellingType($queryText, $originalSpellingType);

                foreach ($rewrites as $rewrittenQuery => $weight) {
                    $synonymQueries[] = $proceed($containerConfig, $rewrittenQuery, $synonymQueriesSpellcheck, $weight, $depth + 1);
                }

                $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $synonymQueries]);
            }

            $this->rewritesCache[$rewriteCacheKey] = $query;
        }

        return $this->rewritesCache[$rewriteCacheKey];
    }

    /**
     * Get weighted rewrites for a given query text.
     * Returns an associative array of ['rewritten query' => weight] if any matches are found.
     *
     * @param string|array                    $queryText       The query text
     * @param ContainerConfigurationInterface $containerConfig Container Configuration
     * @param float                           $originalBoost   Original boost of the query
     *
     * @return array
     */
    private function getWeightedRewrites($queryText, $containerConfig, $originalBoost)
    {
        $rewrites = [];

        if (!is_array($queryText)) {
            $queryText = [$queryText];
        }

        foreach ($queryText as $currentQueryText) {
            // Use + instead of array_merge because $queryText can be purely numeric and would be casted to 0 by array_merge.
            $rewrites = $rewrites + $this->index->getQueryRewrites($containerConfig, $currentQueryText, $originalBoost);
        }

        $maxRewrittenQueries = $this->getThesaurusConfig($containerConfig)->getMaxRewrittenQueries();
        if ($maxRewrittenQueries > 0) {
            $rewrites = array_slice($rewrites, 0, $maxRewrittenQueries, true);
        }

        return $rewrites;
    }

    /**
     * Returns the spelling type to use for rewritten queries.
     * For multi terms queries, considering that at least one term has been replaced,
     * but there could be at least one mistyped term that might not have been replaced.
     * So "elevate" the spelling type a bit.
     * Ideally a new spellcheck query should be run.
     *
     * @param string|array $originalQueryText    The original query text.
     * @param int          $originalSpellingType The original spelling type.
     *
     * @return int
     */
    private function getRewritesSpellingType($originalQueryText, $originalSpellingType)
    {
        if (is_array($originalQueryText)) {
            // Expected to be SPELLING_TYPE_EXACT as enforced by the request builder.
            return $originalSpellingType;
        }

        if ($this->textHelper->mbWordCount($originalQueryText) === 1) {
            return SpellcheckerInterface::SPELLING_TYPE_EXACT;
        }

        $spellingType = $originalSpellingType;

        if (SpellcheckerInterface::SPELLING_TYPE_FUZZY === $originalSpellingType) {
            $spellingType = SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY;
        } elseif (SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS === $originalSpellingType) {
            $spellingType = SpellcheckerInterface::SPELLING_TYPE_MOST_EXACT;
        }

        return $spellingType;
    }

    /**
     * Return thesaurus/relevance configuration.
     *
     * @param ContainerConfigurationInterface $containerConfig Container configuration.
     *
     * @return ThesaurusConfig
     */
    private function getThesaurusConfig(ContainerConfigurationInterface $containerConfig)
    {
        $storeId       = $containerConfig->getStoreId();
        $containerName = $containerConfig->getName();

        return $this->thesaurusConfigFactory->create($storeId, $containerName);
    }
}
