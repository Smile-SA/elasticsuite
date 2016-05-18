<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteThesaurus\Plugin;

use Smile\ElasticSuiteCore\Search\Request\Query\Fulltext\QueryBuilder;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticSuiteThesaurus\Model\Index;
use Smile\ElasticSuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Plugin that handle query rewriting (synonym substitution) during fulltext query building phase.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
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
            $query  = $proceed($containerConfig, $queryText, $spellingType, $boost);

            $rewrites = $this->index->getQueryRewrites($containerConfig, $queryText);

            if (!empty($rewrites)) {
                $synonymQueries = [$query];
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
}
