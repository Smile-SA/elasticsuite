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
use Smile\ElasticSuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticSuiteThesaurus\Config\ThesaurusConfig;

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
     * @var ThesaurusConfigFactory
     */
    private $thesaurusConfigFactory;

    /**
     * @var array
     */
    private $rewritesCache = [];

    /**
     * Constructor.
     *
     * @param QueryFactory           $queryFactory           Search request query factory.
     * @param Index                  $index                  Synonym index.
     * @param ThesaurusConfigFactory $thesaurusConfigFactory Thesaurus configuration.
     */
    public function __construct(QueryFactory $queryFactory, Index $index, ThesaurusConfigFactory $thesaurusConfigFactory)
    {
        $this->queryFactory           = $queryFactory;
        $this->index                  = $index;
        $this->thesaurusConfigFactory = $thesaurusConfigFactory;
    }

    /**
     * Rewrite the query.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryBuilder                    $subject         Original query builder.
     * @param \Closure                        $proceed         Original create func.
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param unknown                         $queryText       Current query text.
     * @param string                          $spellingType    Spelling type of the query.
     * @param number                          $boost           Original query boost.
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
        if (!is_array($queryText)) {
            $queryText = [$queryText];
        }

        $storeId         = $containerConfig->getStoreId();
        $requestName     = $containerConfig->getName();
        $rewriteCacheKey = $requestName . '|' . $storeId . '|' . md5(json_encode($queryText));
        $config          = $this->getConfig($containerConfig);

        if ($config->isSynonymSearchEnabled() && !isset($this->rewritesCache[$rewriteCacheKey])) {
            $synonymQueries = [];
            $query  = $proceed($containerConfig, $queryText, $spellingType, $boost);

            $synonymQueriesSpellcheck = SpellcheckerInterface::SPELLING_TYPE_EXACT;
            $synonymWeightDivider     = $config->getSynonymWeightDivider();

            foreach ($queryText as $simpleQuery) {
                $rewrites = $this->index->getSynonymRewrites($storeId, $simpleQuery);

                foreach ($rewrites as $rewrittenQuery) {
                    $currentBoost = $boost / $synonymWeightDivider;
                    $synonymQueries[] = $proceed($containerConfig, $rewrittenQuery, $synonymQueriesSpellcheck, $currentBoost);
                }

                if (!empty($synonymQueries)) {
                    array_push($synonymQueries, $query);
                    $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $synonymQueries]);
                }
            }

            $this->rewritesCache[$rewriteCacheKey] = $query;
        } elseif (!isset($this->rewritesCache[$rewriteCacheKey])) {
            $this->rewritesCache[$rewriteCacheKey] = $proceed($containerConfig, $queryText, $spellingType, $boost);
        }

        return $this->rewritesCache[$rewriteCacheKey];
    }

    /**
     * Load the thesaurus config for the current container.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     *
     * @return ThesaurusConfig
     */
    private function getConfig(ContainerConfigurationInterface $containerConfig)
    {
        $storeId       = $containerConfig->getStoreId();
        $containerName = $containerConfig->getName();

        return $this->thesaurusConfigFactory->create($storeId, $containerName);
    }
}
