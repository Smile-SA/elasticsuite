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

namespace Smile\ElasticsuiteThesaurus\Model;

use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Helper\Text;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusCacheConfig;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;

/**
 * Thesaurus index.
 *
 * @category  Smile
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
    const WORD_DELIMITER = '_';

    /**
     * @var integer
     */
    const MAX_SIZE = 10;

    /**
     * @var ClientInterface
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
     * @var Text
     */
    private $textHelper;

    /**
     * @var ThesaurusConfig
     */
    private $thesaurusCacheConfig;

    /**
     * Constructor.
     *
     * @param ClientInterface        $client                 ES client.
     * @param IndexSettingsHelper    $indexSettingsHelper    Index Settings Helper.
     * @param CacheHelper            $cacheHelper            ES caching helper.
     * @param Text                   $textHelper             Helper text explaining multibyte string handling.
     * @param ThesaurusConfigFactory $thesaurusConfigFactory Thesaurus configuration factory.
     * @param ThesaurusCacheConfig   $thesaurusCacheConfig   Thesaurus cache configuration helper.
     */
    public function __construct(
        ClientInterface $client,
        IndexSettingsHelper $indexSettingsHelper,
        CacheHelper $cacheHelper,
        Text $textHelper,
        ThesaurusConfigFactory $thesaurusConfigFactory,
        ThesaurusCacheConfig $thesaurusCacheConfig
    ) {
        $this->client                 = $client;
        $this->indexSettingsHelper    = $indexSettingsHelper;
        $this->thesaurusConfigFactory = $thesaurusConfigFactory;
        $this->cacheHelper            = $cacheHelper;
        $this->textHelper             = $textHelper;
        $this->thesaurusCacheConfig   = $thesaurusCacheConfig;
    }

    /**
     * Provides weigthed rewrites for the query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Fulltext query.
     * @param float                           $originalBoost   Original boost of the query
     *
     * @return array
     */
    public function getQueryRewrites(ContainerConfigurationInterface $containerConfig, $queryText, $originalBoost = 1)
    {
        $cacheKey  = $this->getCacheKey($containerConfig, $queryText);
        $cacheTags = $this->getCacheTags($containerConfig);

        $queryRewrites = $this->cacheHelper->loadCache($cacheKey);

        if ($queryRewrites === false) {
            $queryRewrites = $this->computeQueryRewrites($containerConfig, $queryText, $originalBoost);
            if ($this->thesaurusCacheConfig->isCacheStorageAllowed($containerConfig, count($queryRewrites))) {
                $this->cacheHelper->saveCache($cacheKey, $queryRewrites, $cacheTags);
            }
        }

        return $queryRewrites;
    }

    /**
     * Compute weigthed rewrites for the query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Fulltext query.
     * @param float                           $originalBoost   Original boost of the query
     *
     * @return array
     */
    private function computeQueryRewrites(ContainerConfigurationInterface $containerConfig, $queryText, $originalBoost)
    {
        $config   = $this->getConfig($containerConfig);
        $storeId  = $containerConfig->getStoreId();
        $rewrites = [];
        $maxRewrites = $config->getMaxRewrites();

        if ($config->isSynonymSearchEnabled()) {
            $thesaurusType   = ThesaurusInterface::TYPE_SYNONYM;
            $synonymRewrites = $this->getSynonymRewrites($storeId, $queryText, $thesaurusType, $maxRewrites);
            $rewrites        = $this->getWeightedRewrites($synonymRewrites, $config->getSynonymWeightDivider(), $originalBoost);
        }

        if ($config->isExpansionSearchEnabled()) {
            // Use + instead of array_merge because $queryText can be purely numeric and would be casted to 0 by array_merge.
            $synonymRewrites = [(string) $queryText => $originalBoost] + $rewrites;

            foreach ($synonymRewrites as $currentQueryText => $currentWeight) {
                $thesaurusType     = ThesaurusInterface::TYPE_EXPANSION;
                $expansions        = $this->getSynonymRewrites($storeId, $currentQueryText, $thesaurusType, $maxRewrites);
                $expansionRewrites = $this->getWeightedRewrites($expansions, $config->getExpansionWeightDivider(), $currentWeight);
                // Use + instead of array_merge because keys can be purely numeric and would be casted to 0 by array_merge.
                $rewrites          = $rewrites + $expansionRewrites;
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
        $containerName = $containerConfig->getName();

        return [$this->getIndexAlias($storeId), $containerName];
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
     * @param integer $storeId     Store id.
     * @param string  $queryText   Text query.
     * @param string  $type        Substitution type (synonym or expansion).
     * @param integer $maxRewrites Max number of allowed rewrites.
     *
     * @return array
     */
    private function getSynonymRewrites($storeId, $queryText, $type, $maxRewrites)
    {
        $indexName        = $this->getIndexAlias($storeId);
        $analyzedQueries  = $this->getQueryCombinations($storeId, str_replace('-', ' ', $queryText));
        $synonyms         = [];

        foreach ($analyzedQueries as $query) {
            $synonymByPositions = [];

            try {
                $analysis = $this->client->analyze(
                    ['index' => $indexName, 'body' => ['text' => (string) $query, 'analyzer' => $type]]
                );
            } catch (\Exception $e) {
                $analysis = ['tokens' => []];
            }

            foreach ($analysis['tokens'] ?? [] as $token) {
                if ($token['type'] == 'SYNONYM') {
                    $positionKey                        = sprintf('%s_%s', $token['start_offset'], $token['end_offset']);
                    $token['token']                     = str_replace('_', ' ', $token['token']);
                    // Prevent a token already contained in the query to be added.
                    // Eg : you have a synonym between "dress" and "red dress".
                    // If someone search for "red dress", you don't want the final query to be "red red dress".
                    if (array_search($token['token'], str_replace('_', ' ', $analyzedQueries)) === false) {
                        $synonymByPositions[$positionKey][] = $token;
                    }
                }
            }
            // Use + instead of array_merge because keys of the array can be purely numeric and would be casted to 0 by array_merge.
            $synonyms = $synonyms + $this->combineSynonyms(str_replace('_', ' ', $query), $synonymByPositions, $maxRewrites);
        }

        return $synonyms;
    }

    /**
     * Explode the query text and fetch combination of words inside it.
     * Eg : "long sleeve dress" => "long_sleeve dress", "long sleeve_dress", "long_sleeve_dress".
     * This allow to find synonyms for couple of words that are "inside" the complete query.
     * Multi-words synonyms are indexed with "_" as separator.
     *
     * @param int    $storeId   The store id
     * @param string $queryText The base query text
     *
     * @return array
     */
    private function getQueryCombinations($storeId, $queryText)
    {
        if ($this->textHelper->mbWordCount($queryText) < 2) {
            return [$queryText]; // No need to compute variations of shingles with a one-word-query.
        }

        // Generate the shingles.
        // If we analyze "long sleeve dress", we'll obtain "long_sleeve", and "sleeve_dress".
        // We'll also obtain the position (start_offset and end_offset) of those shingles in the original string.
        $indexName = $this->getIndexAlias($storeId);
        try {
            $analysis = $this->client->analyze(
                ['index' => $indexName, 'body' => ['text' => $queryText, 'analyzer' => 'shingles']]
            );
        } catch (\Exception $e) {
            $analysis = ['tokens' => []];
        }

        // Get all variations of the query text by injecting the shingles inside.
        // $tokens = ['long sleeve dress', 'long_sleeve dress', 'long sleeve_dress'];.
        $queries[] = $queryText;
        foreach ($analysis['tokens'] ?? [] as $token) {
            $startOffset        = $token['start_offset'];
            $length             = $token['end_offset'] - $token['start_offset'];
            $rewrittenQueryText = $this->textHelper->mbSubstrReplace($queryText, $token['token'], $startOffset, $length);
            $queries[]          = $rewrittenQueryText;
        }
        $queries = array_unique($queries);

        return $queries;
    }

    /**
     * Combine analysis result to provides all possible synonyms substitution comination.
     *
     * @param string  $queryText          Original query text
     * @param array   $synonymByPositions Synonyms array by positions.
     * @param integer $maxRewrites        Max number of allowed rewrites.
     * @param int     $substitutions      Number of substitutions in the current query.
     * @param int     $offset             Offset of previous substitutions.
     *
     * @return array
     */
    private function combineSynonyms($queryText, $synonymByPositions, $maxRewrites, $substitutions = 0, $offset = 0)
    {
        $combinations = [];

        if (!empty($synonymByPositions) && $substitutions < $maxRewrites) {
            $currentPositionSynonyms = current($synonymByPositions);
            $remainingSynonyms = array_slice($synonymByPositions, 1);

            foreach ($currentPositionSynonyms as $synonym) {
                $startOffset = $synonym['start_offset'] + $offset;
                $length      = $synonym['end_offset'] - $synonym['start_offset'];
                $rewrittenQueryText = $this->textHelper->mbSubstrReplace($queryText, $synonym['token'], $startOffset, $length);
                $newOffset = mb_strlen($rewrittenQueryText) - mb_strlen($queryText) + $offset;
                $combinations[$rewrittenQueryText] = $substitutions + 1;

                if (!empty($remainingSynonyms)) {
                    $combinations = array_merge(
                        $combinations,
                        $this->combineSynonyms($rewrittenQueryText, $remainingSynonyms, $maxRewrites, $substitutions + 1, $newOffset)
                    );
                }
            }

            if (!empty($remainingSynonyms)) {
                $combinations = array_merge(
                    $combinations,
                    $this->combineSynonyms($queryText, $remainingSynonyms, $maxRewrites, $substitutions, $offset)
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
