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
use Smile\ElasticsuiteThesaurus\Model\Index;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

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
     * @var Index
     */
    private $index;

    /**
     * @var array
     */
    private $rewritesCache = [];

    /**
     * Constructor.
     *
     * @param QueryFactory $queryFactory Search request query factory.
     * @param Index        $index        Synonym index.
     */
    public function __construct(QueryFactory $queryFactory, Index $index)
    {
        $this->queryFactory           = $queryFactory;
        $this->index                  = $index;
    }

    /**
     * Rewrite the query.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryBuilder                    $subject         Original query builder.
     * @param \Closure                        $proceed         Original create func.
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Current query text.
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
            // Set base query as SPELLING_TYPE_EXACT if synonyms/expansions are found.
            $spellingType = empty($rewrites) ? $spellingType : SpellcheckerInterface::SPELLING_TYPE_EXACT;
            $query        = $proceed($containerConfig, $queryText, $spellingType, $boost, $depth);

            if (!empty($rewrites)) {
                $synonymQueries           = [$query];
                $synonymQueriesSpellcheck = SpellcheckerInterface::SPELLING_TYPE_EXACT;

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

        return $rewrites;
    }
}
