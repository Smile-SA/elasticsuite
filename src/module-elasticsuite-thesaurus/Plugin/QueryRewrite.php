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
 * @copyright 2019 Smile
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
     *
     * @return QueryInterface
     */
    public function aroundCreate(
        QueryBuilder $subject,
        \Closure $proceed,
        ContainerConfigurationInterface $containerConfig,
        $queryText,
        $spellingType,
        $boost = 1
    ) {

        $storeId         = $containerConfig->getStoreId();
        $requestName     = $containerConfig->getName();
        $rewriteCacheKey = $requestName . '|' . $storeId . '|' . md5(json_encode($queryText));

        if (!isset($this->rewritesCache[$rewriteCacheKey])) {
            $rewrites     = $this->getWeightedRewrites($queryText, $containerConfig);
            // Set base query as SPELLING_TYPE_EXACT if synonyms/expansions are found.
            $spellingType = empty($rewrites) ? $spellingType : SpellcheckerInterface::SPELLING_TYPE_EXACT;
            $query        = $proceed($containerConfig, $queryText, $spellingType, $boost);

            if (!empty($rewrites)) {
                $synonymQueries           = [$query];
                $synonymQueriesSpellcheck = SpellcheckerInterface::SPELLING_TYPE_EXACT;

                foreach ($rewrites as $rewrittenQuery => $weight) {
                    $synonymQueries[] = $proceed($containerConfig, $rewrittenQuery, $synonymQueriesSpellcheck, $weight);
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
     *
     * @return array
     */
    private function getWeightedRewrites($queryText, $containerConfig)
    {
        $rewrites = [];

        if (!is_array($queryText)) {
            $queryText = [$queryText];
        }

        foreach ($queryText as $currentQueryText) {
            $rewrites = array_merge($rewrites, $this->index->getQueryRewrites($containerConfig, $currentQueryText));
        }

        return $rewrites;
    }
}
