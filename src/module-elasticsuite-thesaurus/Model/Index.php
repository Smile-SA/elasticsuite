<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Model;

use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;

/**
 * Thesaurus index.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Index
{
    /**
     * @var string
     */
    const INDEX_IDENTIER = 'thesaurus';

    /**
     * @var string
     */
    const WORD_DELIMITER = '-';

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var ThesaurusConfigFactory
     */
    private $thesaurusConfigFactory;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * Constructor.
     *
     * @param ClientFactoryInterface $clientFactory          ES Client Factory.
     * @param IndexSettingsHelper    $indexSettingsHelper    Index Settings Helper.
     * @param CacheHelper            $cacheHelper            ES caching helper.
     * @param ThesaurusConfigFactory $thesaurusConfigFactory Thesaurus configuration factory.
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        IndexSettingsHelper $indexSettingsHelper,
        CacheHelper $cacheHelper,
        ThesaurusConfigFactory $thesaurusConfigFactory
    ) {
        $this->client                 = $clientFactory->createClient();
        $this->indexSettingsHelper    = $indexSettingsHelper;
        $this->thesaurusConfigFactory = $thesaurusConfigFactory;
        $this->cacheHelper            = $cacheHelper;
    }

    /**
     * Provides weigthed rewrites for the query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Fulltext query.
     *
     * @return array
     */
    public function getQueryRewrites(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $cacheKey  = $this->getCacheKey($containerConfig, $queryText);
        $cacheTags = $this->getCacheTags($containerConfig);

        $queryRewrites = $this->cacheHelper->loadCache($cacheKey);

        if ($queryRewrites === false) {
            $queryRewrites = $this->computeQueryRewrites($containerConfig, $queryText);
            $this->cacheHelper->saveCache($cacheKey, $queryRewrites, $cacheTags);
        }

        return $queryRewrites;
    }

    /**
     * Compute weigthed rewrites for the query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Fulltext query.
     *
     * @return array
     */
    private function computeQueryRewrites(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $config   = $this->getConfig($containerConfig);
        $storeId  = $containerConfig->getStoreId();
        $rewrites = [];

        if ($config->isSynonymSearchEnabled()) {
            $synonymRewrites = $this->getSynonymRewrites($storeId, $queryText, ThesaurusInterface::TYPE_SYNONYM);
            $rewrites        = $this->getWeightedRewrites($synonymRewrites, $config->getSynonymWeightDivider());
        }

        if ($config->isExpansionSearchEnabled()) {
            $synonymRewrites = array_merge([$queryText => 1], $rewrites);

            foreach ($synonymRewrites as $currentQueryText => $currentWeight) {
                $expansions        = $this->getSynonymRewrites($storeId, $currentQueryText, ThesaurusInterface::TYPE_EXPANSION);
                $expansionRewrites = $this->getWeightedRewrites($expansions, $config->getExpansionWeightDivider(), $currentWeight);
                $rewrites = array_merge($rewrites, $expansionRewrites);
            }
        }

        return $rewrites;
    }

    /**
     * Returns the cache key of the query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search container configuration.
     * @param string                          $queryText       Fulltext query.
     *
     * @return string
     */
    private function getCacheKey(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $tags = $this->getCacheTags($containerConfig);

        return implode('|', array_merge($tags, [$queryText]));
    }

    /**
     * Returns cache tags associated to the request.
     *
     * @param ContainerConfigurationInterface $containerConfig Search container configuration.
     *
     * @return string[]
     */
    private function getCacheTags(ContainerConfigurationInterface $containerConfig)
    {
        $storeId = $containerConfig->getStoreId();

        return [$this->getIndexAlias($storeId)];
    }

    /**
     * Returns the index alias used by store id.
     *
     * @param integer $storeId Store id.
     *
     * @return string
     */
    private function getIndexAlias($storeId)
    {
        return $this->indexSettingsHelper->getIndexAliasFromIdentifier(self::INDEX_IDENTIER, $storeId);
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

    /**
     * Generates all possible synonym rewrites for a store and text query.
     *
     * @param integer $storeId   Store id.
     * @param string  $queryText Text query.
     * @param string  $type      Substitution type (synonym or expansion).
     *
     * @return array
     */
    private function getSynonymRewrites($storeId, $queryText, $type)
    {
        $indexName = $this->getIndexAlias($storeId);

        try {
            $analysis = $this->client->indices()->analyze(
                ['index' => $indexName, 'text' => $queryText, 'analyzer' => $type]
            );
        } catch (\Exception $e) {
            $analysis = ['tokens' => []];
        }

        $synonymByPositions = [];

        foreach ($analysis['tokens'] as $token) {
            if ($token['type'] == 'SYNONYM') {
                $positionKey = sprintf('%s_%s', $token['start_offset'], $token['end_offset']);
                $synonymByPositions[$positionKey][] = $token;
            }
        }

        return $this->combineSynonyms($queryText, $synonymByPositions);
    }

    /**
     * Combine analysis result to provides all possible synonyms substitution comination.
     *
     * @param string $queryText          Original query text
     * @param array  $synonymByPositions Synonyms array by positions.
     * @param int    $substitutions      Number of substitutions in the current query.
     * @param int    $offset             Offset of previous substitutions.
     *
     * @return array
     */
    private function combineSynonyms($queryText, $synonymByPositions, $substitutions = 0, $offset = 0)
    {
        $combinations = [];

        if (!empty($synonymByPositions)) {
            $currentPositionSynonyms = current($synonymByPositions);
            $remainingSynonyms = array_slice($synonymByPositions, 1);

            foreach ($currentPositionSynonyms as $synonym) {
                $startOffset = $synonym['start_offset'] + $offset;
                $length      = $synonym['end_offset'] - $synonym['start_offset'];
                $rewrittenQueryText = substr_replace($queryText, $synonym['token'], $startOffset, $length);
                $newOffset = strlen($rewrittenQueryText) - strlen($queryText) + $offset;
                $combinations[$rewrittenQueryText] = $substitutions + 1;

                if (!empty($remainingSynonyms)) {
                    $combinations = array_merge(
                        $combinations,
                        $this->combineSynonyms($rewrittenQueryText, $remainingSynonyms, $substitutions + 1, $newOffset)
                    );
                }
            }

            if (!empty($remainingSynonyms)) {
                $combinations = array_merge(
                    $combinations,
                    $this->combineSynonyms($queryText, $remainingSynonyms, $substitutions, $offset)
                );
            }
        }

        return $combinations;
    }

    /**
     * Convert number of substitution into search queries boost.
     *
     * @param array $queryRewrites Array of query rewrites.
     * @param int   $divider       Score divider.
     * @param int   $baseWeight    Original score.
     *
     * @return array
     */
    private function getWeightedRewrites($queryRewrites, $divider, $baseWeight = 1)
    {
        $mapper = function ($substitutions) use ($baseWeight, $divider) {
            return $baseWeight / ($substitutions * $divider);
        };

        return array_map($mapper, $queryRewrites);
    }
}
