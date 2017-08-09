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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite;

use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;

/**
 * Spellchecker Elasticsearch implementation.
 * This implementation rely on the ES term vectors API.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Spellchecker implements SpellcheckerInterface
{
    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * Constructor.
     *
     * @param ClientFactoryInterface $clientFactory ES Client Factory.
     * @param CacheHelper            $cacheHelper   ES cache helper.
     */
    public function __construct(ClientFactoryInterface $clientFactory, CacheHelper $cacheHelper)
    {
        $this->client      = $clientFactory->createClient();
        $this->cacheHelper = $cacheHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getSpellingType(RequestInterface $request)
    {
        $cacheKey = $this->getCacheKey($request);

        $spellingType = $this->cacheHelper->loadCache($cacheKey);

        if ($spellingType === false) {
            $spellingType = $this->loadSpellingType($request);
            $this->cacheHelper->saveCache($cacheKey, $spellingType, [$request->getIndex()]);
        }

        return $spellingType;
    }

    /**
     * Compute the spelling time using the engine.
     *
     * @param RequestInterface $request Spellchecking request.
     *
     * @return integer
     */
    private function loadSpellingType(RequestInterface $request)
    {
        $cutoffFrequencyLimit = $this->getCutoffrequencyLimit($request);
        $termVectors          = $this->getTermVectors($request);
        $queryTermStats       = $this->parseTermVectors($termVectors, $cutoffFrequencyLimit);

        $spellingType = self::SPELLING_TYPE_FUZZY;

        if ($queryTermStats['total'] == $queryTermStats['stop']) {
            $spellingType = self::SPELLING_TYPE_PURE_STOPWORDS;
        } elseif ($queryTermStats['total'] == $queryTermStats['stop'] + $queryTermStats['exact']) {
            $spellingType = self::SPELLING_TYPE_EXACT;
        } elseif ($queryTermStats['missing'] == 0) {
            $spellingType = self::SPELLING_TYPE_MOST_EXACT;
        } elseif ($queryTermStats['total'] - $queryTermStats['missing'] > 0) {
            $spellingType = self::SPELLING_TYPE_MOST_FUZZY;
        }

        return $spellingType;
    }

    /**
     * Compute an unique caching key for the spellcheck request.
     *
     * @param RequestInterface $request Spellchecking request.
     *
     * @return string
     */
    private function getCacheKey(RequestInterface $request)
    {
        return implode('|', [$request->getIndex(), $request->getType(), $request->getQueryText()]);
    }

    /**
     * Count document into the index and then multiply it by the request cutoff frequency
     * to compute an absolute cutoff frequency limit (max numbre of doc).
     *
     * @param RequestInterface $request The spellcheck request.
     *
     * @return int
     */
    private function getCutoffrequencyLimit(RequestInterface $request)
    {
        $indexStatsResponse = $this->client->indices()->stats(['index' => $request->getIndex()]);
        $indexStats         = current($indexStatsResponse['indices']);
        $totalIndexedDocs = $indexStats['total']['docs']['count'];

        return $request->getCutoffFrequency() * $totalIndexedDocs;
    }

    /**
     * Run a term vectors query against the index and return the result.
     *
     * @param RequestInterface $request The spellcheck request.
     *
     * @return array
     */
    private function getTermVectors(RequestInterface $request)
    {
        $termVectorsQuery = [
            'index'           => $request->getIndex(),
            'type'            => $request->getType(),
            'id'              => '',
            'term_statistics' => true,
            'dfs'             => true,
        ];

        $termVectorsQuery['body']['doc'] = [MappingInterface::DEFAULT_SPELLING_FIELD => $request->getQueryText()];

        return $this->client->termvectors($termVectorsQuery);
    }

    /**
     * Parse the terms vectors to extract stats on the query.
     * Result is an array containing :
     * - total    : number of terms into the query
     * - stop     : number of stopwords into the query
     * - exact    : number of terms correctly spelled into the query
     * - missing  : number of terms of the query not found into the index
     * - standard : number of terms of the query found using the standard analyzer.
     *
     * @param array $termVectors          The term vector query response.
     * @param int   $cutoffFrequencyLimit Cutoff freq (max absolute number of docs to consider term as a stopword).
     *
     * @return array
     */
    private function parseTermVectors($termVectors, $cutoffFrequencyLimit)
    {
        $queryTermStats = ['stop' => 0, 'exact' => 0, 'standard' => 0, 'missing' => 0];
        $statByPosition = $this->extractTermStatsByPoisition($termVectors);

        foreach ($statByPosition as $positionStat) {
            $type = 'missing';
            if ($positionStat['doc_freq'] > 0) {
                $type = 'standard';
                if ($positionStat['doc_freq'] >= $cutoffFrequencyLimit) {
                    $type = 'stop';
                } elseif (in_array(FieldInterface::ANALYZER_WHITESPACE, $positionStat['analyzers'])) {
                    $type = 'exact';
                }
            }
            $queryTermStats[$type]++;
        }

        $queryTermStats['total'] = count($statByPosition);

        return $queryTermStats;
    }

    /**
     * Extract term stats by position from a term vectors query response.
     * Wil return an array of doc_freq, analayzers and term by position.
     *
     * @param array $termVectors The term vector query response.
     *
     * @return array
     */
    private function extractTermStatsByPoisition($termVectors)
    {
        $statByPosition = [];
        $analyzers      = [FieldInterface::ANALYZER_STANDARD, FieldInterface::ANALYZER_WHITESPACE];

        foreach ($termVectors['term_vectors'] as $propertyName => $fieldData) {
            $analyzer = $this->getAnalyzer($propertyName);
            if (in_array($analyzer, $analyzers)) {
                foreach ($fieldData['terms'] as $term => $termStats) {
                    foreach ($termStats['tokens'] as $token) {
                        $positionKey = sprintf("%s_%s", $token['start_offset'], $token['end_offset']);

                        if (!isset($termStats['doc_freq'])) {
                            $termStats['doc_freq'] = 0;
                        }

                        if (!isset($statByPosition[$positionKey])) {
                            $statByPosition[$positionKey]['term']     = $term;
                            $statByPosition[$positionKey]['doc_freq'] = $termStats['doc_freq'];
                        }

                        if ($termStats['doc_freq']) {
                            $statByPosition[$positionKey]['analyzers'][] = $analyzer;
                        }

                        $statByPosition[$positionKey]['doc_freq'] = max(
                            $termStats['doc_freq'],
                            $statByPosition[$positionKey]['doc_freq']
                        );
                    }
                }
            }
        }

        return $statByPosition;
    }

    /**
     * Extract analayser from a mapping property name.
     *
     * @param string $propertyName Property name (eg. : search.whitespace)
     *
     * @return string
     */
    private function getAnalyzer($propertyName)
    {
        $analyzer = FieldInterface::ANALYZER_STANDARD;

        if (strstr($propertyName, '.')) {
            $propertyNameParts = explode('.', $propertyName);
            $analyzer = end($propertyNameParts);
        }

        return $analyzer;
    }
}
