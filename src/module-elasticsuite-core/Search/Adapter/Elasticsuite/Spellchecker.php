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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite;

use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config\ScopePool;

/**
 * Spellchecker Elasticsearch implementation.
 * This implementation rely on the ES term vectors API.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Spellchecker implements SpellcheckerInterface
{
    /**
     * Max number of characters allowed as a gap between two reference-analyzer-produced sibling tokens
     * when reconstructing a synthetic concatenation, to account for a single stripped separator
     * character (space, hyphen, ...).
     */
    private const CONCATENATION_GAP_TOLERANCE = 2;

    /**
     * Separator-like characters a reference-analyzer concatenation may have stripped between two words:
     * whitespace (space, tab, ...), hyphen (-), underscore (_), comma (,), period/dot (.), forward slash (/).
     */
    private const CONCATENATION_GAP_SEPARATOR_PATTERN = '/^[\s\-_,.\/]+$/u';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var array
     */
    private $indexStatsCache = [];

    /**
     * Constructor.
     *
     * @param ClientInterface $client      ES Client Factory.
     * @param CacheHelper     $cacheHelper ES cache helper.
     */
    public function __construct(ClientInterface $client, CacheHelper $cacheHelper)
    {
        $this->client      = $client;
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
            $this->cacheHelper->saveCache($cacheKey, $spellingType, [$request->getIndex(), ScopePool::CACHE_TAG]);
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
        $spellingType = self::SPELLING_TYPE_FUZZY;

        try {
            $cutoffFrequencyLimit = $this->getCutoffrequencyLimit($request);
            $termVectors          = $this->getTermVectors($request);
            $excludeConcatenationFalseNegatives = $request->isUsingAllTokens()
                && $request->isUsingReference()
                && $request->isExcludingConcatenationFalseNegatives();
            $queryTermStats       = $this->parseTermVectors(
                $termVectors,
                $cutoffFrequencyLimit,
                $request->isUsingAllTokens(),
                $excludeConcatenationFalseNegatives,
                $request->getQueryText()
            );

            if ($queryTermStats['total'] == $queryTermStats['stop']) {
                $spellingType = self::SPELLING_TYPE_PURE_STOPWORDS;
            } elseif ($queryTermStats['total'] == $queryTermStats['stop'] + $queryTermStats['exact']) {
                $spellingType = self::SPELLING_TYPE_EXACT;
            } elseif ($queryTermStats['missing'] == 0) {
                $spellingType = self::SPELLING_TYPE_MOST_EXACT;
            } elseif ($queryTermStats['total'] - $queryTermStats['missing'] > 0) {
                $spellingType = self::SPELLING_TYPE_MOST_FUZZY;
            }
        } catch (\Exception $e) {
            $spellingType = self::SPELLING_TYPE_EXACT;
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
        return implode('|', [$request->getIndex(), $request->getQueryText()]);
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
        $indexStatsResponse = $this->getIndexStats($request->getIndex());
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
        $stats = $this->getIndexStats($request->getIndex());
        // Get number of shards.
        $shards = (int) ($stats['_shards']['successful'] ?? 1);

        $doc = [
            '_index'          => $request->getIndex(),
            '_type'           => '_doc',
            'term_statistics' => true,
            'fields'          => [
                MappingInterface::DEFAULT_SPELLING_FIELD,
                MappingInterface::DEFAULT_SPELLING_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                MappingInterface::DEFAULT_SEARCH_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
            ],
            'doc'             => [
                MappingInterface::DEFAULT_SEARCH_FIELD   => $request->getQueryText(),
                MappingInterface::DEFAULT_SPELLING_FIELD => $request->getQueryText(),
            ],
        ];
        $perFieldAnalyzer = [];

        if ($request->isUsingReference()) {
            $doc['fields'][] = MappingInterface::DEFAULT_REFERENCE_FIELD . "." . FieldInterface::ANALYZER_REFERENCE;
            $doc['doc'][MappingInterface::DEFAULT_REFERENCE_FIELD] = $request->getQueryText();
        }

        if ($request->isUsingEdgeNgram()) {
            $doc['fields'][] = MappingInterface::DEFAULT_EDGE_NGRAM_FIELD . "." . FieldInterface::ANALYZER_EDGE_NGRAM;
            $perFieldAnalyzer[MappingInterface::DEFAULT_EDGE_NGRAM_FIELD . "." . FieldInterface::ANALYZER_EDGE_NGRAM]
                = FieldInterface::ANALYZER_STANDARD;
            $doc['doc'][MappingInterface::DEFAULT_EDGE_NGRAM_FIELD] = $request->getQueryText();
        }

        if (!empty($perFieldAnalyzer)) {
            $doc['per_field_analyzer'] = $perFieldAnalyzer;
        }

        $docs = [];

        // Compute the mtermvector query on all indices.
        foreach (array_keys($stats['indices']) as $indexName) {
            // Compute the mtermvector query on all shards to ensure exhaustive results.
            foreach (range(0, $shards - 1) as $shard) {
                $doc['_index'] = $indexName;
                $doc['routing'] = sprintf("[%s][%s]", $indexName, $shard);
                $docs[] = $doc;
            }
        }

        $mtermVectorsQuery['body'] = ['docs' => $docs];

        return $this->client->mtermvectors($mtermVectorsQuery);
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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param array   $termVectors                        The term vector query response.
     * @param int     $cutoffFrequencyLimit               Cutoff freq (max absolute number of docs to consider term as a stopword).
     * @param boolean $useAllTokens                       Whether to use all tokens or not
     * @param boolean $excludeConcatenationFalseNegatives Whether to exclude reference analyzer word-concatenation artifacts.
     * @param string  $queryText                          The original query text (used by the exclusion check above).
     *
     * @return array
     */
    private function parseTermVectors(
        $termVectors,
        $cutoffFrequencyLimit,
        $useAllTokens = false,
        $excludeConcatenationFalseNegatives = false,
        $queryText = ''
    ) {
        $queryTermStats = ['stop' => 0, 'exact' => 0, 'standard' => 0, 'missing' => 0];
        $statByPosition = $this->extractTermStatsByPosition($termVectors, $useAllTokens);

        if ($useAllTokens && $excludeConcatenationFalseNegatives) {
            $statByPosition = $this->excludeConcatenationFalseNegatives($statByPosition, (string) $queryText);
        }

        foreach ($statByPosition as $positionStat) {
            $type = 'missing';
            if ($positionStat['doc_freq'] > 0) {
                $type = 'standard';
                if ($positionStat['doc_freq'] >= $cutoffFrequencyLimit) {
                    $type = 'stop';
                } elseif (in_array(FieldInterface::ANALYZER_WHITESPACE, $positionStat['analyzers'])) {
                    $type = 'exact';
                } elseif (in_array(FieldInterface::ANALYZER_REFERENCE, $positionStat['analyzers'])) {
                    $type = 'exact';
                } elseif (in_array(FieldInterface::ANALYZER_EDGE_NGRAM, $positionStat['analyzers'])) {
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
     * Will return an array of doc_freq, analyzers and term by position.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param array   $termVectors  The term vector query response.
     * @param boolean $useAllTokens Whether to use all tokens returned in the term vector response.
     *
     * @return array
     */
    private function extractTermStatsByPosition($termVectors, $useAllTokens = false)
    {
        $statByPosition = [];
        $analyzers      = [
            FieldInterface::ANALYZER_STANDARD,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_REFERENCE,
            FieldInterface::ANALYZER_EDGE_NGRAM,
        ];

        if (is_array($termVectors) && isset($termVectors['docs'])) {
            foreach ($termVectors['docs'] as $termVector) {
                foreach ($termVector['term_vectors'] as $propertyName => $fieldData) {
                    $analyzer = $this->getAnalyzer($propertyName);
                    if (in_array($analyzer, $analyzers)) {
                        foreach ($fieldData['terms'] as $term => $termStats) {
                            foreach ($termStats['tokens'] as $token) {
                                $positionKey = $token['position'];
                                if ($useAllTokens) {
                                    $positionKey = "{$token['position']}_{$token['start_offset']}_{$token['end_offset']}";
                                }

                                if (!isset($termStats['doc_freq'])) {
                                    $termStats['doc_freq'] = 0;
                                }

                                if (!isset($statByPosition[$positionKey])) {
                                    $statByPosition[$positionKey]['term']     = $term;
                                    $statByPosition[$positionKey]['doc_freq'] = $termStats['doc_freq'];
                                    // Raw positional metadata, only meaningful when $useAllTokens is
                                    // true (otherwise several distinct spans collapse into this key).
                                    $statByPosition[$positionKey]['position']     = $token['position'];
                                    $statByPosition[$positionKey]['start_offset'] = $token['start_offset'];
                                    $statByPosition[$positionKey]['end_offset']   = $token['end_offset'];
                                    $statByPosition[$positionKey]['producing_analyzers'] = [];
                                }

                                if ($termStats['doc_freq']) {
                                    $statByPosition[$positionKey]['analyzers'][] = $analyzer;
                                }

                                // Unlike 'analyzers' above, this tracks every analyzer that ever
                                // tokenized this exact entry, regardless of whether it matched.
                                if (!in_array($analyzer, $statByPosition[$positionKey]['producing_analyzers'], true)) {
                                    $statByPosition[$positionKey]['producing_analyzers'][] = $analyzer;
                                }

                                $statByPosition[$positionKey]['doc_freq'] = max(
                                    $termStats['doc_freq'],
                                    $statByPosition[$positionKey]['doc_freq']
                                );
                            }
                        }
                    }
                }
            }
        }

        return $statByPosition;
    }

    /**
     * Exclude "missing" term vector entries that are actually false negatives: artifacts of the reference
     * analyzer concatenating adjacent, independently existing query words (e.g. "leather"+"bag" -> "leatherbag").
     *
     * Only entries produced exclusively by the reference analyzer, and fully reconstructible from at
     * least two sibling entries (each already found, at strictly increasing positions, separated from
     * each other by a genuine stripped-separator gap) are removed. This intentionally does not match
     * same-word fragment decomposition (e.g. SKU "AN328CZ127" -> "AN"+"328"+...), because such fragments
     * are always offset-adjacent with a zero-character gap between them (no separator was ever stripped).
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param array  $statByPosition Term vector stats, keyed by "{position}_{start_offset}_{end_offset}".
     * @param string $queryText      Original query text, used to validate stripped-separator characters.
     *
     * @return array
     */
    private function excludeConcatenationFalseNegatives(array $statByPosition, $queryText)
    {
        $goodEntries = [];
        foreach ($statByPosition as $entry) {
            if (!empty($entry['doc_freq']) && isset($entry['position'], $entry['start_offset'], $entry['end_offset'])) {
                $goodEntries[] = $entry;
            }
        }

        usort($goodEntries, function ($entryA, $entryB) {
            return $entryA['start_offset'] <=> $entryB['start_offset'];
        });

        foreach ($statByPosition as $key => $entry) {
            if (!isset($entry['position'], $entry['start_offset'], $entry['end_offset'])) {
                continue;
            }
            if (!empty($entry['doc_freq'])) {
                continue;
            }
            if ($entry['end_offset'] <= $entry['start_offset']) {
                continue;
            }

            $producingAnalyzers = array_values(array_unique($entry['producing_analyzers'] ?? []));
            if ($producingAnalyzers !== [FieldInterface::ANALYZER_REFERENCE]) {
                continue;
            }

            if ($this->coversSpan($goodEntries, $entry['start_offset'], $entry['end_offset'], -1, 0, $queryText)) {
                unset($statByPosition[$key]);
            }
        }

        return $statByPosition;
    }

    /**
     * Recursively check whether [$start, $end) can be fully reconstructed by chaining sibling entries
     * that are each already found, at strictly increasing positions, with a zero-character gap before
     * the very first piece and a non-zero, separator-only gap before every subsequent piece.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array  $goodEntries          Candidate sibling entries (doc_freq > 0), sorted by start_offset.
     * @param int    $start                Remaining span start offset.
     * @param int    $end                  Remaining span end offset (exclusive).
     * @param int    $minPositionExclusive Minimum (exclusive) token position the next piece must have.
     * @param int    $depth                Number of pieces already chained.
     * @param string $queryText            Original query text.
     *
     * @return bool
     */
    private function coversSpan(array $goodEntries, $start, $end, $minPositionExclusive, $depth, $queryText)
    {
        if ($start === $end) {
            return $depth >= 2;
        }

        foreach ($goodEntries as $piece) {
            if ($piece['position'] <= $minPositionExclusive) {
                continue;
            }
            if ($piece['start_offset'] < $start || $piece['end_offset'] > $end) {
                continue;
            }

            $gap = $piece['start_offset'] - $start;

            if ($depth === 0) {
                if ($gap !== 0) {
                    continue;
                }
            } else {
                if ($gap < 1 || $gap > self::CONCATENATION_GAP_TOLERANCE) {
                    continue;
                }
                if ($queryText !== '') {
                    $gapText = mb_substr($queryText, $start, $gap);
                    if (!preg_match(self::CONCATENATION_GAP_SEPARATOR_PATTERN, $gapText)) {
                        continue;
                    }
                }
            }

            if ($this->coversSpan($goodEntries, $piece['end_offset'], $end, $piece['position'], $depth + 1, $queryText)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract analyser from a mapping property name.
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

    /**
     * Get index stats.
     *
     * @param string $indexName The index name
     *
     * @return array
     */
    private function getIndexStats(string $indexName): array
    {
        if (!isset($this->indexStatsCache[$indexName])) {
            $this->indexStatsCache[$indexName] = $this->client->indexStats(['index' => $indexName]);
        }

        return $this->indexStatsCache[$indexName];
    }
}
