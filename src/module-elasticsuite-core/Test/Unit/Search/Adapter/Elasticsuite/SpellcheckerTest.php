<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Helper\Cache;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Spellchecker;

/**
 * Spellchecker basic unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class SpellcheckerTest extends TestCase
{
    /**
     * Test basic term vector params.
     *
     * @return void
     */
    public function testTermVectorsParams()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsQuery = [
            'body' => [
                'docs' => [
                    [
                        'routing'         => "[{$indexName}][0]",
                        '_index'          => $indexName,
                        'term_statistics' => true,
                        'fields'          => [
                            MappingInterface::DEFAULT_SPELLING_FIELD,
                            MappingInterface::DEFAULT_SPELLING_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_SEARCH_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                        ],
                        'doc'             => [
                            MappingInterface::DEFAULT_SEARCH_FIELD   => $queryText,
                            MappingInterface::DEFAULT_SPELLING_FIELD => $queryText,
                        ],
                    ],
                ],
            ],
        ];

        /*
         * Not providing any termVectors response will generate a fake "stop" type of request,
         * in parseTermVectors with all queryTermStats set to 0.
         * There probably should be an extra check in the legacy code that if total = 0,
         * a fuzzy request should occur.
         */
        $client->expects($this->exactly(1))->method('mtermvectors')->with(
            $termVectorsQuery
        );
        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $spellchecker->getSpellingType($request);
    }

    /**
     * Test term vector params when using the reference analyzer is enabled in settings.
     *
     * @return void
     */
    public function testReferenceTermVectorsParams()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(true);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsQuery = [
            'body' => [
                'docs' => [
                    [
                        'routing'         => "[{$indexName}][0]",
                        '_index'          => $indexName,
                        'term_statistics' => true,
                        'fields'          => [
                            MappingInterface::DEFAULT_SPELLING_FIELD,
                            MappingInterface::DEFAULT_SPELLING_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_SEARCH_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_REFERENCE_FIELD . "." . FieldInterface::ANALYZER_REFERENCE,
                        ],
                        'doc'             => [
                            MappingInterface::DEFAULT_SEARCH_FIELD   => $queryText,
                            MappingInterface::DEFAULT_SPELLING_FIELD => $queryText,
                            MappingInterface::DEFAULT_REFERENCE_FIELD => $queryText,
                        ],
                    ],
                ],
            ],
        ];

        /*
         * Not providing any termVectors response will generate a fake "stop" type of request,
         * in parseTermVectors with all queryTermStats set to 0.
         * There probably should be an extra check in the legacy code that if total = 0,
         * a fuzzy request should occur.
         */
        $client->expects($this->exactly(1))->method('mtermvectors')->with(
            $termVectorsQuery
        );
        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $spellchecker->getSpellingType($request);
    }

    /**
     * Test term vector params when using the standard_edge_ngram analyzer is enabled in settings.
     *
     * @return void
     */
    public function testEdgeNgramTermVectorsParams()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(true);

        $termVectorsQuery = [
            'body' => [
                'docs' => [
                    [
                        'routing'         => "[{$indexName}][0]",
                        '_index'          => $indexName,
                        'term_statistics' => true,
                        'fields'          => [
                            MappingInterface::DEFAULT_SPELLING_FIELD,
                            MappingInterface::DEFAULT_SPELLING_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_SEARCH_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_EDGE_NGRAM_FIELD . "." . FieldInterface::ANALYZER_EDGE_NGRAM,
                        ],
                        'doc'             => [
                            MappingInterface::DEFAULT_SEARCH_FIELD   => $queryText,
                            MappingInterface::DEFAULT_SPELLING_FIELD => $queryText,
                            MappingInterface::DEFAULT_EDGE_NGRAM_FIELD => $queryText,
                        ],
                    ],
                ],
            ],
        ];

        /*
         * Not providing any termVectors response will generate a fake "stop" type of request,
         * in parseTermVectors with all queryTermStats set to 0.
         * There probably should be an extra check in the legacy code that if total = 0,
         * a fuzzy request should occur.
         */
        $client->expects($this->exactly(1))->method('mtermvectors')->with(
            $termVectorsQuery
        );
        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $spellchecker->getSpellingType($request);
    }

    /**
     * Test term vector params when using the reference and standard_edge_ngram analyzer
     * are both enabled in settings.
     *
     * @return void
     */
    public function testReferenceAndEdgeNgramTermVectorsParams()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(true);
        $request->method('isUsingEdgeNgram')->willReturn(true);

        $termVectorsQuery = [
            'body' => [
                'docs' => [
                    [
                        'routing'         => "[{$indexName}][0]",
                        '_index'          => $indexName,
                        'term_statistics' => true,
                        'fields'          => [
                            MappingInterface::DEFAULT_SPELLING_FIELD,
                            MappingInterface::DEFAULT_SPELLING_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_SEARCH_FIELD . "." . FieldInterface::ANALYZER_WHITESPACE,
                            MappingInterface::DEFAULT_REFERENCE_FIELD . "." . FieldInterface::ANALYZER_REFERENCE,
                            MappingInterface::DEFAULT_EDGE_NGRAM_FIELD . "." . FieldInterface::ANALYZER_EDGE_NGRAM,
                        ],
                        'doc'             => [
                            MappingInterface::DEFAULT_SEARCH_FIELD   => $queryText,
                            MappingInterface::DEFAULT_SPELLING_FIELD => $queryText,
                            MappingInterface::DEFAULT_REFERENCE_FIELD => $queryText,
                            MappingInterface::DEFAULT_EDGE_NGRAM_FIELD => $queryText,
                        ],
                    ],
                ],
            ],
        ];

        /*
         * Not providing any termVectors response will generate a fake "stop" type of request,
         * in parseTermVectors with all queryTermStats set to 0.
         * There probably should be an extra check in the legacy code that if total = 0,
         * a fuzzy request should occur.
         */
        $client->expects($this->exactly(1))->method('mtermvectors')->with(
            $termVectorsQuery
        );
        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $spellchecker->getSpellingType($request);
    }

    /**
     * Test that the spelling type is determined exact when the search term is found in the index
     * for the default fields/analyzers but under the default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeExact()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 12,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined exact when the search term is found in the index
     * for the default fields/analyzers and the reference analyzer but under the default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeExactUsingReference()
    {
        $indexName  = 'index';
        $queryText  = 'ABC125ZX37';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(true);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                '125' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                '37' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 8,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc125zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 6,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                '125' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                '37' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 8,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc125zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 6,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                '125' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                '37' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 8,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'abc125zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 6,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'reference.reference' => [
                            'terms' => [
                                '125' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 3,
                                ],
                                '125zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 2,
                                ],
                                '125zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 1,
                                ],
                                '37' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 8,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 4,
                                ],
                                'abc' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 5,
                                ],
                                'abc125' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 2,
                                ],
                                'abc125zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 1,
                                ],
                                'abc125zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 1,
                                ],
                                'zx' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 6,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 3,
                                ],
                                'zx37' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 6,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                    'doc_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined exact when the search term is found in the index
     * for the default fields/analyzers and the standard_edge_ngram analyzer
     * but under the default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeExactUsingEdgeNgram()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(true);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                            ],
                        ],
                        'edge_ngram.standard_edge_ngram' => [
                            'terms' => [
                                'whe' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 1,
                                ],
                                'whee' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 1,
                                ],
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type could be wrongly determined exact when searching for a sku-like term
     * and some tokens resulting from the (standard, whitespace) world_delimiter are not taken into account
     * due to summarizing token stats by position only.
     * This lead to the introduction of the "use_all_tokens" search relevance option.
     * In this test case, all small components are reported present in the index but not the whole term,
     * but only the small present components are taken into account, leading to an exact match query.
     * Which could lead to a 0 result exact search page if the minimum should match is strict (for instance 100%).
     *
     * @return void
     */
    public function testInvalidSpellingTypeExactWithoutReferenceWithoutAllTokens()
    {
        $indexName  = 'index';
        $queryText  = 'AN328CZ127';
        $indexStats = $this->getSingleShardStats($indexName);
        $indexStats['indices'][$indexName]['total']['docs']['count'] = 15000000;

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1165,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3501,
                                    'ttf' => 17432,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 2,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type could be wrongly determined exact when searching for a sku-like term
     * and some tokens resulting from the (standard, whitespace, reference) world_delimiter are not taken into account
     * due to summarizing token stats by position only.
     * This lead to the introduction of the "use_all_tokens" search relevance option.
     * In this test case, all small components are reported present in the index but not the whole term,
     * but only the small present components are taken into account, leading to an exact match query.
     * Which could lead to a 0 result exact search page if the minimum should match is strict (for instance 100%).
     * This version includes asking the term vectors for "reference" analyzed tokens.
     *
     * @return void
     */
    public function testInvalidSpellingTypeExactWithReferenceWithoutAllTokens()
    {
        $indexName  = 'index';
        $queryText  = 'AN328CZ127';
        $indexStats = $this->getSingleShardStats($indexName);
        $indexStats['indices'][$indexName]['total']['docs']['count'] = 15000000;

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'reference.reference' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 798,
                                    'ttf' => 2144,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 356,
                                    'ttf' => 1017,
                                    'term_freq' => 1,
                                ],
                                '328cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                '328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3616,
                                    'ttf' => 10050,
                                    'term_freq' => 1,
                                ],
                                'an328' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an328cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 297,
                                    'ttf' => 891,
                                    'term_freq' => 1,
                                ],
                                'cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1165,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3501,
                                    'ttf' => 17432,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 2,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is correctly determined when searching for a sku-like term
     * and all tokens resulting from the (standard, whitespace) world_delimiter are taken into account
     * due to summarizing token stats by position and offsets due to the "use_all_tokens" search relevance option.
     * In this test case, all small components are reported present in the index but not the whole term.
     * Since all tokens are taken into account, a mix of exact and missing tokens leads to a most fuzzy query.
     *
     * @return void
     */
    public function testValidSpellingTypeMostFuzzyWithoutReferenceWithAllTokens()
    {
        $indexName  = 'index';
        $queryText  = 'AN328CZ127';
        $indexStats = $this->getSingleShardStats($indexName);
        $indexStats['indices'][$indexName]['total']['docs']['count'] = 15000000;

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(true);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1165,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3501,
                                    'ttf' => 17432,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 2,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is correctly determined when searching for a sku-like term
     * and all tokens resulting from the (standard, whitespace, reference) world_delimiter are taken into account
     * due to summarizing token stats by position and offsets due to the "use_all_tokens" search relevance option.
     * In this test case, all small components are reported present in the index but not the whole term.
     * Since all tokens are taken into account, a mix of exact and missing tokens leads to a most fuzzy query.
     * This version includes asking the term vectors for "reference" analyzed tokens.
     *
     * @return void
     */
    public function testValidSpellingTypeMostFuzzyWithReferenceWithAllTokens()
    {
        $indexName  = 'index';
        $queryText  = 'AN328CZ127';
        $indexStats = $this->getSingleShardStats($indexName);
        $indexStats['indices'][$indexName]['total']['docs']['count'] = 15000000;

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(true);

        $request->method('isUsingReference')->willReturn(true);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'reference.reference' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 798,
                                    'ttf' => 2144,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 356,
                                    'ttf' => 1017,
                                    'term_freq' => 1,
                                ],
                                '328cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                '328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3616,
                                    'ttf' => 10050,
                                    'term_freq' => 1,
                                ],
                                'an328' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an328cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 297,
                                    'ttf' => 891,
                                    'term_freq' => 1,
                                ],
                                'cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'spelling' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1165,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3501,
                                    'ttf' => 17432,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 1,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                '127' => [
                                    'tokens' => [
                                        [
                                            'position' => 3,
                                            'start_offset' => 7,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 629,
                                    'ttf' => 2725,
                                    'term_freq' => 1,
                                ],
                                '328' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 2,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 251,
                                    'ttf' => 1155,
                                    'term_freq' => 1,
                                ],
                                'an' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 2,
                                        ],
                                    ],
                                    'doc_freq' => 3399,
                                    'ttf' => 16921,
                                    'term_freq' => 1,
                                ],
                                'an328cz127' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                        // Probably because of preserve_original=true.
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'term_freq' => 2,
                                ],
                                'cz' => [
                                    'tokens' => [
                                        [
                                            'position' => 2,
                                            'start_offset' => 5,
                                            'end_offset' => 7,
                                        ],
                                    ],
                                    'doc_freq' => 253,
                                    'ttf' => 1265,
                                    'term_freq' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined stop when the search term is found in the index
     * for the default fields/analyzers but above the default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeStop()
    {
        $indexName  = 'index';
        $queryText  = 'wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 5,
                                        ],
                                    ],
                                    'doc_freq' => 17,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 6,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined exact when the search terms are found in the index
     * for the default fields/analyzers both under and above the default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeExactWithStopWords()
    {
        $indexName  = 'index';
        $queryText  = 'iron wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'iron' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 4,
                                        ],
                                    ],
                                    'doc_freq' => 3,
                                ],
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 4,
                                            'end_offset' => 9,
                                        ],
                                    ],
                                    'doc_freq' => 17,
                                ],
                            ],
                        ],
                        'spelling.whitespace' => [
                            'terms' => [
                                'iron' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 4,
                                        ],
                                    ],
                                    'doc_freq' => 3,
                                ],
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 4,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                        'search.whitespace' => [
                            'terms' => [
                                'iron' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 4,
                                        ],
                                    ],
                                    'doc_freq' => 3,
                                ],
                                'wheels' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 4,
                                            'end_offset' => 10,
                                        ],
                                    ],
                                    'doc_freq' => 8,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined most exact when the search term(s) are found in the index
     * for non-whitespace default fields/analyzers with at least one term under default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeMostExact()
    {
        $indexName  = 'index';
        $queryText  = 'reddish wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'red' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'doc_freq' => 4,
                                ],
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'doc_freq' => 17,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_MOST_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined most fuzzy when not all terms are found in the index
     * using default fields/analyzers under default cut-off frequency threshold.
     *
     * @return void
     */
    public function testSpellingTypeMostFuzzy()
    {
        $indexName  = 'index';
        $queryText  = 'reddish wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'red' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'doc_freq' => 17,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY, $spellchecker->getSpellingType($request));
    }

    /**
     * Test that the spelling type is determined fuzzy when none of the terms are found in the index (all are missing)
     * using default fields/analyzers.
     *
     * @return void
     */
    public function testSpellingTypeFuzzy()
    {
        $indexName  = 'index';
        $queryText  = 'reddish wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $termVectorsResponse = [
            'docs' => [
                [
                    'term_vectors' => [
                        'spelling' => [
                            'terms' => [
                                'red' => [
                                    'tokens' => [
                                        [
                                            'position' => 0,
                                            'start_offset' => 0,
                                            'end_offset' => 3,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                                'wheel' => [
                                    'tokens' => [
                                        [
                                            'position' => 1,
                                            'start_offset' => 3,
                                            'end_offset' => 8,
                                        ],
                                    ],
                                    'doc_freq' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client->method('mtermvectors')->willReturn(
            $termVectorsResponse
        );

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_FUZZY, $spellchecker->getSpellingType($request));
    }


    /**
     * Test that the spelling type is determined exact when an exception occurs while fetching term vectors.
     *
     * @return void
     */
    public function testSpellingTypeExactIfException()
    {
        $indexName  = 'index';
        $queryText  = 'reddish wheels';
        $indexStats = $this->getSingleShardStats($indexName);

        $client = $this->getClientMock();
        $client->method('indexStats')->willReturn($indexStats);

        $request = $this->getRequestMock($indexName, $queryText);
        $request->method('isUsingAllTokens')->willReturn(false);

        $request->method('isUsingReference')->willReturn(false);
        $request->method('isUsingEdgeNgram')->willReturn(false);

        $client->method('mtermvectors')->willThrowException(new \Exception());

        $spellchecker = new Spellchecker($client, $this->getCacheMock());
        $this->assertEquals(SpellcheckerInterface::SPELLING_TYPE_EXACT, $spellchecker->getSpellingType($request));
    }

    /**
     * Get mock index stats for a single shard index.
     *
     * @param string $indexName Index name
     *
     * @return array
     */
    private function getSingleShardStats($indexName)
    {
        return [
            'indices' => [
                $indexName => [
                    'total' => [
                        'docs' => [
                            'count' => 100,
                        ],
                    ],
                ],
            ],
            '_shards' => [
                'successful' => 1,
            ],
        ];
    }

    /**
     * Get cache mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Cache
     */
    private function getCacheMock()
    {
        $cacheMock = $this->getMockBuilder(Cache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cacheMock->method('loadCache')->willReturn(false);

        return $cacheMock;
    }

    /**
     * Get client mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ClientInterface
     */
    private function getClientMock()
    {
        return $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get a request mock with a given index and query.
     *
     * @param string $indexName  Index name
     * @param string $queryText  Query
     * @param float  $cutOffFreq Cut-off frequency value
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|RequestInterface
     */
    private function getRequestMock($indexName = 'index', $queryText = 'query', $cutOffFreq = 0.15)
    {
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->method('getIndex')->willReturn($indexName);
        $request->method('getQueryText')->willReturn($queryText);
        $request->method('getCutoffFrequency')->willReturn($cutOffFreq);

        return $request;
    }
}
