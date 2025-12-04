<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Model;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Helper\Cache;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusCacheConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusStemmingConfig;
use Smile\ElasticsuiteThesaurus\Helper\Text as TextHelper;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Thesaurus Index model unit test.
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test cache usage and lack of rewrites.
     * @dataProvider noRewriteDataProvider
     *
     * @param string $queryText                Initial query text.
     * @param bool   $synonymsEnabled          Thesaurus config synonym enabled switch.
     * @param int    $synonymWeightDivider     Thesaurus config synonym weight divider.
     * @param bool   $expansionEnabled         Thesaurus config expansion enabled switch.
     * @param int    $expansionWeightDivider   Thesaurus config expansion weight divider.
     * @param int    $maxRewrites              Thesaurus config max rewrites.
     * @param int    $timesClientCalled        Expected number of times the client 'analyze' method will be called.
     * @param array  $clientConsecutiveReturns Array of mocked returns from client 'analyze' method.
     * @param array  $expectedRewrites         Expected final array of rewritten queries.
     * @param bool   $cacheStorageAllowed      Whether saving results in cache is allowed.
     * @param string $containerName            Container name/request type.
     * @param int    $storeId                  Store id.
     * @param string $storeCode                Store code.
     * @param string $indexPrefix              Global config index alias/prefix.
     *
     * @return void
     */
    public function testCacheUsageNoRewrites(
        $queryText,
        $synonymsEnabled,
        $synonymWeightDivider,
        $expansionEnabled,
        $expansionWeightDivider,
        $maxRewrites,
        $timesClientCalled,
        $clientConsecutiveReturns,
        $expectedRewrites,
        $cacheStorageAllowed = true,
        $containerName = 'requestType',
        $storeId = 1,
        $storeCode = 'default',
        $indexPrefix = 'magento2'
    ) {
        $clientMock                 = $this->getClientMock();
        $indexSettingsHelperMock    = $this->getIndexSettingsHelperMock();
        $cacheHelperMock            = $this->getCacheHelperMock();
        $thesaurusConfigMock        = $this->getThesaurusConfigMock(
            $synonymsEnabled,
            $synonymWeightDivider,
            $expansionEnabled,
            $expansionWeightDivider,
            $maxRewrites
        );
        $thesaurusConfigFactoryMock = $this->getThesaurusConfigFactoryMock($thesaurusConfigMock);
        $thesaurusCacheConfigMock = $this->getThesaurusCacheConfigMock($cacheStorageAllowed);

        $indexAlias = sprintf('%s_%s_%s', $indexPrefix, $storeCode, ThesaurusIndex::INDEX_IDENTIER);
        $indexSettingsHelperMock->method('getIndexAliasFromIdentifier')->willReturn($indexAlias);

        $clientMock->expects($this->exactly($timesClientCalled))->method('analyze')
            ->willReturnOnConsecutiveCalls(
                $clientConsecutiveReturns
            );

        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig->method('getStoreId')->willReturn($storeId);
        $containerConfig->method('getName')->willReturn($containerName);

        $stemmingConfig = $this->getMockBuilder(ThesaurusStemmingConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stemmingConfig->method('useStemming')->willReturn(true);

        $thesaurusIndex = new ThesaurusIndex(
            $clientMock,
            $indexSettingsHelperMock,
            $cacheHelperMock,
            $thesaurusConfigFactoryMock,
            $thesaurusCacheConfigMock,
            new TextHelper(),
            $stemmingConfig
        );

        $cacheKey = implode('|', [$indexAlias, $containerName, $queryText]);
        $cacheTags = [$indexAlias, $containerName];

        $cacheHelperMock->expects($this->exactly(1))->method('loadCache')->with($cacheKey)
            ->willReturn(false);
        $cacheHelperMock->expects($this->exactly((int) $cacheStorageAllowed))->method('saveCache')->with(
            $cacheKey,
            $expectedRewrites,
            $cacheTags
        );

        $rewrites = $thesaurusIndex->getQueryRewrites($containerConfig, $queryText);
        $this->assertEquals($expectedRewrites, $rewrites);
    }

    /**
     * Data provider for testCacheUsageNoRewrites.
     *
     * @return array
     */
    public function noRewriteDataProvider()
    {
        /*
         * [queryText, synonymsEnabled, $synonymWeightDivider, expansionEnabled, expansionWeightDivider, $maxRewrites,
         *      timesClientCall, clientConsecutiveReturns, expectedRewrites(, $cacheStorageAllowed)].
         */
        return [
            ['foo', false, 10, false, 10, 2,
                0, [], [],
            ],
            ['foo', true, 10, false, 10, 2,
                2, [[]], [],
            ],
            ['foo', true, 10, true, 10, 2,
                4, [[], []], [],
            ],
            ['foo', true, 10, true, 10, 2,
                4, [[], []], [], false,
            ],
        ];
    }

    /**
     * Test single level rewrites.
     * @dataProvider singleLevelRewritesDataProvider
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string $queryText                Initial query text.
     * @param bool   $synonymsEnabled          Thesaurus config synonym enabled switch.
     * @param int    $synonymWeightDivider     Thesaurus config synonym weight divider.
     * @param bool   $expansionEnabled         Thesaurus config expansion enabled switch.
     * @param int    $expansionWeightDivider   Thesaurus config expansion weight divider.
     * @param int    $maxRewrites              Thesaurus config max rewrites.
     * @param int    $timesClientCalled        Expected number of times the client 'analyze' method will be called.
     * @param array  $clientConsecutiveReturns Array of mocked returns from client 'analyze' method.
     * @param array  $expectedRewrites         Expected final array of rewritten queries.
     * @param bool   $cacheStorageAllowed      Whether saving results in cache is allowed.
     * @param string $containerName            Container name/request type.
     * @param int    $storeId                  Store id.
     * @param string $storeCode                Store code.
     * @param string $indexPrefix              Global config index alias/prefix.
     *
     * @return void
     */
    public function testSingleLevelRewrites(
        $queryText,
        $synonymsEnabled,
        $synonymWeightDivider,
        $expansionEnabled,
        $expansionWeightDivider,
        $maxRewrites,
        $timesClientCalled,
        $clientConsecutiveReturns,
        $expectedRewrites,
        $cacheStorageAllowed = true,
        $containerName = 'requestType',
        $storeId = 1,
        $storeCode = 'default',
        $indexPrefix = 'magento2'
    ) {
        $clientMock                 = $this->getClientMock();
        $indexSettingsHelperMock    = $this->getIndexSettingsHelperMock();
        $cacheHelperMock            = $this->getCacheHelperMock();
        $thesaurusConfigMock        = $this->getThesaurusConfigMock(
            $synonymsEnabled,
            $synonymWeightDivider,
            $expansionEnabled,
            $expansionWeightDivider,
            $maxRewrites
        );
        $thesaurusConfigFactoryMock = $this->getThesaurusConfigFactoryMock($thesaurusConfigMock);
        $thesaurusCacheConfigMock = $this->getThesaurusCacheConfigMock($cacheStorageAllowed);

        $indexAlias = sprintf('%s_%s_%s', $indexPrefix, $storeCode, ThesaurusIndex::INDEX_IDENTIER);
        $indexSettingsHelperMock->method('getIndexAliasFromIdentifier')->willReturn($indexAlias);

        $analyzeMethod = $clientMock->expects($this->exactly($timesClientCalled))->method('analyze');
        if (array_key_exists('map', $clientConsecutiveReturns)) {
            $clientReturnMaps = $clientConsecutiveReturns['map'];
            $clientReturnMaps = array_map(
                function ($mapItem) use ($indexAlias) {
                    $baseParams = current($mapItem);
                    $results = next($mapItem);

                    return [['index' => $indexAlias, 'body' => $baseParams], $results];
                },
                $clientReturnMaps
            );
            $analyzeMethod->willReturnMap($clientReturnMaps);
        } else {
            $analyzeMethod->willReturnOnConsecutiveCalls(...$clientConsecutiveReturns);
        }

        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig->method('getStoreId')->willReturn($storeId);
        $containerConfig->method('getName')->willReturn($containerName);

        $stemmingConfig = $this->getMockBuilder(ThesaurusStemmingConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stemmingConfig->method('useStemming')->willReturn(true);

        $thesaurusIndex = new ThesaurusIndex(
            $clientMock,
            $indexSettingsHelperMock,
            $cacheHelperMock,
            $thesaurusConfigFactoryMock,
            $thesaurusCacheConfigMock,
            new TextHelper(),
            $stemmingConfig
        );

        $cacheKey = implode('|', array_merge([$indexAlias, $containerName], [$queryText]));
        $cacheTags = [$indexAlias, $containerName];

        $cacheHelperMock->expects($this->exactly(1))->method('loadCache')->with($cacheKey)
            ->willReturn(false);
        $cacheHelperMock->expects($this->exactly((int) $cacheStorageAllowed))->method('saveCache')->with(
            $cacheKey,
            $expectedRewrites,
            $cacheTags
        );

        $rewrites = $thesaurusIndex->getQueryRewrites($containerConfig, $queryText);
        $this->assertEquals($expectedRewrites, $rewrites);
    }

    /**
     * Data provider for testSingleLevelRewrites.
     *
     * @return array
     */
    public function singleLevelRewritesDataProvider()
    {
        /*
         * [queryText, synonymsEnabled, $synonymWeightDivider, expansionEnabled, expansionWeightDivider, $maxRewrites,
         *      timesClientCall, clientConsecutiveReturns, expectedRewrites(, cacheStorageAllowed)].
         */
        return [
            // Both synonyms and expansions disabled.
            ['foo', false, 10, false, 10, 2,
                0, [], [],
            ],
            // Only synonyms enabled. Simulating 'foo,bar,baz'.
            ['foo', true, 10, false, 10, 2,
                2,
                [
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    'bar' => 0.1,
                    'baz' => 0.1,
                ],
            ],
            // Only synonyms enabled. Simulating 'foo,bar,baz'.
            // Same test as before, but client->analyze returns expressed as a mapping.
            ['foo', true, 10, false, 10, 2,
                2,
                [
                    'map' => [
                        [
                            ['text' => 'foo', 'analyzer' => 'clean'],
                            [
                                'tokens' => [
                                    [
                                        'type' => '<ALPHANUM>',
                                        'token' => 'foo',
                                        'start_offset' => 0,
                                        'end_offset' => 3,
                                        'position' => 0,
                                    ],
                                ],
                            ],
                        ],
                        [
                            ['text' => 'foo', 'analyzer' => 'synonym'],
                            [
                                'tokens' => [
                                    [
                                        'type' => 'SYNONYM',
                                        'token' => 'bar',
                                        'start_offset' => 0,
                                        'end_offset' => 3,
                                        'position' => 0,
                                    ],
                                    [
                                        'type' => 'SYNONYM',
                                        'token' => 'baz',
                                        'start_offset' => 0,
                                        'end_offset' => 3,
                                        'position' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'bar' => 0.1,
                    'baz' => 0.1,
                ],
            ],
            // Only expansions enabled. Simulating 'foo => bar,baz'.
            ['foo', false, 10, true, 10, 2,
                2,
                [
                    [
                        ['text' => 'foo', 'analyzer' => 'clean'],
                        [
                            'tokens' => [
                                [
                                    'type' => '<ALPHANUM>',
                                    'token' => 'foo',
                                    'start_offset' => 0,
                                    'end_offset' => 3,
                                    'position' => 0,
                                ],
                            ],
                        ],
                    ],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    'bar' => 0.1,
                    'baz' => 0.1,
                ],
            ],
            // Both synonyms and expansions enabled. Simulating 'foo,bar,baz' and 'bar => pub,cafe'.
            ['foo', true, 10, true, 10, 2,
                8,
                [
                    // Stem call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Synonyms call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stem call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansions call.
                    // No expansion for 'foo'.
                    ['tokens' => []],
                    // Stem call for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stem call for 'baz'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // No expansion for 'baz'.
                    ['tokens' => []],
                ],
                [
                    // Synonyms only for 'foo'.
                    'bar' => 0.1,
                    'baz' => 0.1,
                    'pub' => 0.01,
                    'cafe' => 0.01,
                ],
            ],
            // Both synonyms and expansions enabled. Simulating 'foo,bar,baz' and 'bar => pub,cafe'.
            // No cache storage allowed.
            ['foo', true, 10, true, 10, 2,
                8,
                [
                    // Stem call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Synonyms call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stem call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansions call.
                    // No expansion for 'foo'.
                    ['tokens' => []],
                    // Stem call for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stem call for 'baz'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // No expansion for 'baz'.
                    ['tokens' => []],
                ],
                [
                    // Synonyms only for 'foo'.
                    'bar' => 0.1,
                    'baz' => 0.1,
                    'pub' => 0.01,
                    'cafe' => 0.01,
                ],
                false,
            ],
            // Both synonyms and expansions enabled, multi-words search.
            // Simulating 'foo,bat,baz' and 'bar => pub,cafe'.
            // Carefull, the client is also called in getQueryCombinations.
            ['foo bar', true, 10, true, 10, 2,
                12,
                [
                    // Synonyms::getQueryCombinations call for 'foo bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Synonyms call for 'foo bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bat',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Synonyms call for 'foo_bar'.
                    ['tokens' => []],
                    // Expansion::getQueryCombinations call for 'foo bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion call for '(foo) bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                        ],
                    ],
                    // Expansion call for 'foo_bar'.
                    ['tokens' => []],
                    // Expansion::getQueryCombinations call for 'bat bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'bat_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion call for '(bat) bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                        ],
                    ],
                    // Expansion call for 'bat_bar'.
                    ['tokens' => []],
                    // Expansion::getQueryCombinations call for 'baz bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'baz_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion call for '(baz) bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                        ],
                    ],
                    // Expansion call for 'baz_bar'.
                    ['tokens' => []],
                ],
                [
                    // Synonyms only for 'foo (bar)'.
                    'bat bar' => 0.1,
                    'baz bar' => 0.1,
                    // Expansions only for '(foo) bar'.
                    'foo pub' => 0.1,
                    'foo cafe' => 0.1,
                    // Expansions for '(bat) bar'.
                    'bat pub' => 0.01,
                    'bat cafe' => 0.01,
                    // Expansions for '(baz) bar'.
                    'baz pub' => 0.01,
                    'baz cafe' => 0.01,
                ],
            ],
        ];
    }

    /**
     * Test multi-level rewrites combination.
     * @dataProvider multiLevelRewritesDataProvider
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string $queryText                Initial query text.
     * @param bool   $synonymsEnabled          Thesaurus config synonym enabled switch.
     * @param int    $synonymWeightDivider     Thesaurus config synonym weight divider.
     * @param bool   $expansionEnabled         Thesaurus config expansion enabled switch.
     * @param int    $expansionWeightDivider   Thesaurus config expansion weight divider.
     * @param int    $maxRewrites              Thesaurus config max rewrites.
     * @param int    $timesClientCalled        Expected number of times the client 'analyze' method will be called.
     * @param array  $clientConsecutiveReturns Array of mocked returns from client 'analyze' method.
     * @param array  $expectedRewrites         Expected final array of rewritten queries.
     * @param bool   $cacheStorageAllowed      Whether saving results in cache is allowed.
     * @param string $containerName            Container name/request type.
     * @param int    $storeId                  Store id.
     * @param string $storeCode                Store code.
     * @param string $indexPrefix              Global config index alias/prefix.
     *
     * @return void
     */
    public function testMultiLevelRewritesCombination(
        $queryText,
        $synonymsEnabled,
        $synonymWeightDivider,
        $expansionEnabled,
        $expansionWeightDivider,
        $maxRewrites,
        $timesClientCalled,
        $clientConsecutiveReturns,
        $expectedRewrites,
        $cacheStorageAllowed = true,
        $containerName = 'requestType',
        $storeId = 1,
        $storeCode = 'default',
        $indexPrefix = 'magento2'
    ) {
        $clientMock                 = $this->getClientMock();
        $indexSettingsHelperMock    = $this->getIndexSettingsHelperMock();
        $cacheHelperMock            = $this->getCacheHelperMock();
        $thesaurusConfigMock        = $this->getThesaurusConfigMock(
            $synonymsEnabled,
            $synonymWeightDivider,
            $expansionEnabled,
            $expansionWeightDivider,
            $maxRewrites
        );
        $thesaurusConfigFactoryMock = $this->getThesaurusConfigFactoryMock($thesaurusConfigMock);
        $thesaurusCacheConfigMock = $this->getThesaurusCacheConfigMock($cacheStorageAllowed);

        $indexAlias = sprintf('%s_%s_%s', $indexPrefix, $storeCode, ThesaurusIndex::INDEX_IDENTIER);
        $indexSettingsHelperMock->method('getIndexAliasFromIdentifier')->willReturn($indexAlias);

        $analyzeMethod = $clientMock->expects($this->exactly($timesClientCalled))->method('analyze');
        if (array_key_exists('map', $clientConsecutiveReturns)) {
            $clientReturnMaps = $clientConsecutiveReturns['map'];
            $clientReturnMaps = array_map(
                function ($mapItem) use ($indexAlias) {
                    $baseParams = current($mapItem);
                    $results = next($mapItem);

                    return [['index' => $indexAlias, 'body' => $baseParams], $results];
                },
                $clientReturnMaps
            );
            $analyzeMethod->willReturnMap($clientReturnMaps);
        } else {
            $analyzeMethod->willReturnOnConsecutiveCalls(...$clientConsecutiveReturns);
        }

        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig->method('getStoreId')->willReturn($storeId);
        $containerConfig->method('getName')->willReturn($containerName);

        $stemmingConfig = $this->getMockBuilder(ThesaurusStemmingConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stemmingConfig->method('useStemming')->willReturn(true);

        $thesaurusIndex = new ThesaurusIndex(
            $clientMock,
            $indexSettingsHelperMock,
            $cacheHelperMock,
            $thesaurusConfigFactoryMock,
            $thesaurusCacheConfigMock,
            new TextHelper(),
            $stemmingConfig
        );

        $cacheKey = implode('|', array_merge([$indexAlias, $containerName], [$queryText]));
        $cacheTags = [$indexAlias, $containerName];

        $cacheHelperMock->expects($this->exactly(1))->method('loadCache')->with($cacheKey)
            ->willReturn(false);
        $cacheHelperMock->expects($this->exactly((int) $cacheStorageAllowed))->method('saveCache')->with(
            $cacheKey,
            $expectedRewrites,
            $cacheTags
        );

        $rewrites = $thesaurusIndex->getQueryRewrites($containerConfig, $queryText);
        $this->assertEquals($expectedRewrites, $rewrites);

        $this->assertTrue(true);
    }

    /**
     * Data provider for testMultiLevelRewritesCombination.
     *
     * @return array
     */
    public function multiLevelRewritesDataProvider()
    {
        /*
         * Results map for rules:
         * synonyms: foo,bar' and 'foobar,foo bar' and 'bar,pipe,tube'.
         * expansion: 'bar => pub,cafe'.
         */
        $cyclingMappingResults = [
            'map' => [
                [
                    // Synonyms::getQueryCombinations call for 'foo bar'.
                    ['text' => 'foo bar', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ], // => Produce queries ['foo bar', 'foo_bar'].
                ],
                [
                    // Synonyms call for 'foo bar'.
                    ['text' => 'foo bar', 'analyzer' => 'synonym'],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_foo',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_foo_pipe',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_foo_pipe_tube',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 4,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'foo',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'foo_pipe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'foo_pipe_tube',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pipe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pipe_tube',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 2,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'tube',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 3,
                            ],
                        ],
                    ],
                ],
                [
                    // Synonyms call for 'foo_bar'.
                    ['text' => 'foo_bar', 'analyzer' => 'synonym'],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'foobar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for '(foo) bar'.
                    ['text' => 'foo bar', 'analyzer' => 'expansion'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => '__cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => '__cafe_pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 2,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'foo_bar'.
                    ['text' => 'foo_bar', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'bar bar'.
                    ['text' => 'bar bar', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'bar_bar',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar bar'.
                    ['text' => 'bar bar', 'analyzer' => 'expansion'],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub_cafe',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub_cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 4,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub_cafe',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub_cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 2,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 4,
                                'end_offset' => 7,
                                'position' => 3,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar_bar'.
                    ['text' => 'bar_bar', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'bar foo'.
                    ['text' => 'bar foo', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'bar_foo',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    ['text' => 'bar foo', 'analyzer' => 'expansion'],
                    // Expansion call for 'bar foo'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub__',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub__',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar_foo'.
                    ['text' => 'bar_foo', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'bar pipe'.
                    ['text' => 'bar pipe', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'bar_pipe',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar pipe'.
                    ['text' => 'bar pipe', 'analyzer' => 'expansion'],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub__',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub__',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar_pipe'.
                    ['text' => 'bar_pipe', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'bar tube'.
                    ['text' => 'bar tube', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'bar_tube',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar tube'.
                    ['text' => 'bar tube', 'analyzer' => 'expansion'],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub__',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub__',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'bar_tube'.
                    ['text' => 'bar_tube', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'foo foo'.
                    ['text' => 'foo foo', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_foo',
                                'start_offset' => 0,
                                'end_offset' => 7,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'foo foo'.
                    ['text' => 'foo foo', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion call for 'foo_foo'.
                    ['text' => 'foo_foo', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'foo pipe'.
                    ['text' => 'foo pipe', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_pipe',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'foo pipe'.
                    ['text' => 'foo pipe', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion call for 'foo pipe'.
                    ['text' => 'foo_pipe', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion::getQueryCombinations call for 'foo tube'.
                    ['text' => 'foo tube', 'analyzer' => 'shingles'],
                    [
                        'tokens' => [
                            [
                                'type' => 'shingle',
                                'token' => 'foo_tube',
                                'start_offset' => 0,
                                'end_offset' => 8,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // Expansion call for 'foo tube'.
                    ['text' => 'foo tube', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion call for 'foo_tube'.
                    ['text' => 'foo_tube', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
                [
                    // Expansion call for 'foobar'.
                    ['text' => 'foobar', 'analyzer' => 'expansion'],
                    ['tokens' => []],
                ],
            ],
        ];

        /*
         * [queryText, synonymsEnabled, $synonymWeightDivider, expansionEnabled, expansionWeightDivider, $maxRewrites,
         *      timesClientCall, clientConsecutiveReturns, expectedRewrites].
         */
        return [
            // Both synonyms and expansions disabled.
            ['foo', false, 10, false, 10, 2,
                0, [], [],
            ],
            // Only synonyms enabled. Simulating 'foo,bar,baz' and 'bar,pub,cafe' and 'bar,pipe,tube'.
            ['foo', true, 10, false, 10, 2,
                2,
                [
                    // Stemm call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    'bar' => 0.1,
                    'baz' => 0.1,
                ],
            ],
            // Only expansions enabled. Simulating 'foo => bar,baz' and 'bar => pub,cafe' and 'bar => pipe,tube'.
            ['foo', false, 10, true, 10, 2,
                2,
                [
                    // Stemm call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    'bar' => 0.1,
                    'baz' => 0.1,
                ],
            ],
            // Both synonyms and expansions enabled.
            // Simulating 'foo,bar,baz' and 'bar,pub,cafe' and 'bar,pipe,tube'.
            // and 'foo => bar,baz' and 'bar => pub,cafe' and 'bar => pipe,tube'.
            ['foo', true, 10, true, 100, 2,
                8,
                [
                    // Stemm call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Synonyms call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stemm call for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'foo',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansions call.
                    // No expansion for 'foo'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'bar_baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Stemm call for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'bar',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // Expansion for 'bar'.
                    [
                        'tokens' => [
                            [
                                'type' => 'SYNONYM',
                                'token' => 'cafe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub_pipe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'cafe_pub_pipe_tube',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                                'positionLength' => 4,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pub',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub_pipe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pub_pipe_tube',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 1,
                                'positionLength' => 3,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'pipe',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 2,
                            ],
                            [
                                'type' => 'shingle',
                                'token' => 'pipe_tube',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 2,
                                'positionLength' => 2,
                            ],
                            [
                                'type' => 'SYNONYM',
                                'token' => 'tube',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 3,
                            ],
                        ],
                    ],
                    // Stemm call for 'baz'.
                    [
                        'tokens' => [
                            [
                                'type' => '<ALPHANUM>',
                                'token' => 'baz',
                                'start_offset' => 0,
                                'end_offset' => 3,
                                'position' => 0,
                            ],
                        ],
                    ],
                    // No expansion for 'baz'.
                    ['tokens' => []],
                ],
                [
                    // Synonyms only for 'foo'.
                    'baz' => 0.1,
                    'bar' => 0.1,
                    'pub' => 0.001,
                    'cafe' => 0.001,
                    'pipe' => 0.001,
                    'tube' => 0.001,
                ],
            ],
            // Both synonyms and expansions enabled, multi-words search with cycle.
            // Simulating 'foo,bar' and 'foobar,foo bar', 'bar,pipe,tube' and 'bar => pub,cafe'.
            // Carefull, the client is also called in getQueryCombinations.
            ['foo bar', true, 10, true, 10, 2,
                29,
                $cyclingMappingResults,
                [
                    // Synonyms only for 'foo (bar)'.
                    'bar bar' => 0.1,
                    // Synonyms only for 'bar bar' (2nd level).
                    'bar foo' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                    'bar pipe' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                    'bar tube' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                    // Synonyms only for '(foo) bar'.
                    'foo foo' => 0.1,
                    'foo pipe' => 0.1,
                    'foo tube' => 0.1,
                    // Synonyms only for 'foo bar'.
                    'foobar' => 0.1,
                    // Expansions only for '(foo) bar'.
                    'foo cafe' => 0.1,
                    'foo pub' => 0.1,
                    // Expansions for 'bar (bar)'.
                    'cafe bar' => 0.01,
                    // 2nd level expansions.
                    'cafe cafe' => 0.005,
                    'cafe pub' => 0.005,
                    'pub bar' => 0.01,
                    // 2nd level expansions.
                    'pub cafe' => 0.005,
                    'pub pub' => 0.005,
                    // Expansions for '(bar) bar'.
                    'bar cafe' => 0.01,
                    'bar pub' => 0.01,
                    // 2nd level expansions.
                    'cafe foo' => 0.005,
                    'pub foo' => 0.005,
                    // Expansion for 'bar pipe'.
                    'cafe pipe' => 0.005,
                    'pub pipe' => 0.005,
                    // Expansion for 'bar tube'.
                    'cafe tube' => 0.005,
                    'pub tube' => 0.005,
                ],
            ],
            // Both synonyms and expansions enabled, multi-words search with cycle with limiting max rewrites to 1.
            // Simulating 'foo,bar' and 'foobar,foo bar', 'bar,pipe,tube' and 'bar => pub,cafe'.
            // Carefull, the client is also called in getQueryCombinations.
            ['foo bar', true, 10, true, 10, 1,
                20,
                $cyclingMappingResults,
                [
                    // Synonyms only for 'foo (bar)'.
                    'bar bar' => 0.1,
                    // No 2nd level synonyms.
                    /*
                        'bar foo' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                        'bar pipe' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                        'bar tube' => 0.05, // 0.1 / (rewrite level) = 0.1 / 2.
                    */
                    // Synonyms only for '(foo) bar'.
                    'foo foo' => 0.1,
                    'foo pipe' => 0.1,
                    'foo tube' => 0.1,
                    // Synonyms only for 'foo bar'.
                    'foobar' => 0.1,
                    // Expansions only for '(foo) bar'.
                    'foo cafe' => 0.1,
                    'foo pub' => 0.1,
                    // Expansions for 'bar (bar)'.
                    'cafe bar' => 0.01,
                    // No 2nd level expansions.
                    /*
                     * ex:
                        'cafe cafe' => 0.005,
                        'cafe pub' => 0.005,
                    */
                    'pub bar' => 0.01,
                    // Expansions for '(bar) bar'.
                    'bar cafe' => 0.01,
                    'bar pub' => 0.01,
                ],
            ],
        ];
    }

    /**
     * Test behavior when analysis call fails.
     * @dataProvider withAnalysisFailureDataProvider
     *
     * @param string $queryText              Initial query text.
     * @param bool   $synonymsEnabled        Thesaurus config synonym enabled switch.
     * @param int    $synonymWeightDivider   Thesaurus config synonym weight divider.
     * @param bool   $expansionEnabled       Thesaurus config expansion enabled switch.
     * @param int    $expansionWeightDivider Thesaurus config expansion weight divider.
     * @param int    $maxRewrites            Thesaurus config max rewrites.
     * @param int    $timesClientCalled      Expected number of times the client 'analyze' method will be called.
     * @param array  $expectedRewrites       Expected final array of rewritten queries.
     * @param bool   $cacheStorageAllowed    Whether saving results in cache is allowed.
     * @param string $containerName          Container name/request type.
     * @param int    $storeId                Store id.
     * @param string $storeCode              Store code.
     * @param string $indexPrefix            Global config index alias/prefix.
     *
     * @return void
     */
    public function testAnalyzeFailure(
        $queryText,
        $synonymsEnabled,
        $synonymWeightDivider,
        $expansionEnabled,
        $expansionWeightDivider,
        $maxRewrites,
        $timesClientCalled,
        $expectedRewrites,
        $cacheStorageAllowed = true,
        $containerName = 'requestType',
        $storeId = 1,
        $storeCode = 'default',
        $indexPrefix = 'magento2'
    ) {
        $clientMock                 = $this->getClientMock();
        $indexSettingsHelperMock    = $this->getIndexSettingsHelperMock();
        $cacheHelperMock            = $this->getCacheHelperMock();
        $thesaurusConfigMock        = $this->getThesaurusConfigMock(
            $synonymsEnabled,
            $synonymWeightDivider,
            $expansionEnabled,
            $expansionWeightDivider,
            $maxRewrites
        );
        $thesaurusConfigFactoryMock = $this->getThesaurusConfigFactoryMock($thesaurusConfigMock);
        $thesaurusCacheConfigMock = $this->getThesaurusCacheConfigMock($cacheStorageAllowed);

        $indexAlias = sprintf('%s_%s_%s', $indexPrefix, $storeCode, ThesaurusIndex::INDEX_IDENTIER);
        $indexSettingsHelperMock->method('getIndexAliasFromIdentifier')->willReturn($indexAlias);

        $clientMock->expects($this->exactly($timesClientCalled))->method('analyze')
            ->willThrowException(new BadRequest400Exception('Dummy exception'));

        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig->method('getStoreId')->willReturn($storeId);
        $containerConfig->method('getName')->willReturn($containerName);

        $stemmingConfig = $this->getMockBuilder(ThesaurusStemmingConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stemmingConfig->method('useStemming')->willReturn(true);

        $thesaurusIndex = new ThesaurusIndex(
            $clientMock,
            $indexSettingsHelperMock,
            $cacheHelperMock,
            $thesaurusConfigFactoryMock,
            $thesaurusCacheConfigMock,
            new TextHelper(),
            $stemmingConfig
        );

        $cacheKey = implode('|', array_merge([$indexAlias, $containerName], [$queryText]));
        $cacheTags = [$indexAlias, $containerName];

        $cacheHelperMock->expects($this->exactly(1))->method('loadCache')->with($cacheKey)
            ->willReturn(false);
        $cacheHelperMock->expects($this->exactly((int) $cacheStorageAllowed))->method('saveCache')->with(
            $cacheKey,
            $expectedRewrites,
            $cacheTags
        );

        $rewrites = $thesaurusIndex->getQueryRewrites($containerConfig, $queryText);
        $this->assertEquals($expectedRewrites, $rewrites);
    }

    /**
     * With analysis failure data provider.
     *
     * @return array
     */
    public function withAnalysisFailureDataProvider()
    {
        return [
            /*
             * [queryText, containerName, storeId, storeCode, indexPrefix,
             *      synonymsEnabled, $synonymWeightDivider, expansionEnabled, expansionWeightDivider, $maxRewrites,
             *      timesClientCall, expectedRewrites].
             */
            // Both synonyms and expansions disabled.
            ['foo', false, 10, false, 10, 2, 0, []],
            // Only synonyms enabled.
            ['foo', true, 10, false, 10, 2, 2, []],
            // Only expansions enabled.
            ['foo', false, 10, true, 10, 2, 2, []],
            // Both synonyms and expansions enabled.
            ['foo', true, 10, true, 10, 2, 4, []],
            // Both synonyms and expansions enabled, multi-words search.
            // Careful, the client is also called in getQueryCombinations.
            ['foo bar', true, 10, true, 10, 2, 4, []],
        ];
    }

    /**
     * Get client mock.
     *
     * @return MockObject|ClientInterface
     */
    private function getClientMock()
    {
        return $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get Index settings helper mock.
     *
     * @return MockObject|IndexSettings
     */
    private function getIndexSettingsHelperMock()
    {
        return $this->getMockBuilder(IndexSettings::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get Thesaurus Config mock object.
     *
     * @param bool $synonymEnabled         Whether synonyms are enabled.
     * @param int  $synonymWeightDivider   Synonyms weight divider.
     * @param bool $expansionEnabled       Whether expansions are enabled.
     * @param int  $expansionWeightDivider Expansion weight divider.
     * @param int  $maxRewrites            Max allowed rewrites.
     *
     * @return MockObject
     */
    private function getThesaurusConfigMock(
        $synonymEnabled = true,
        $synonymWeightDivider = 10,
        $expansionEnabled = true,
        $expansionWeightDivider = 10,
        $maxRewrites = 2
    ) {
        $thesaurusConfigMock = $this->getMockBuilder(ThesaurusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $thesaurusConfigMock->method('isSynonymSearchEnabled')->willReturn($synonymEnabled);
        $thesaurusConfigMock->method('getSynonymWeightDivider')->willReturn($synonymWeightDivider);
        $thesaurusConfigMock->method('isExpansionSearchEnabled')->willReturn($expansionEnabled);
        $thesaurusConfigMock->method('getExpansionWeightDivider')->willReturn($expansionWeightDivider);
        $thesaurusConfigMock->method('getMaxRewrites')->willReturn($maxRewrites);

        return $thesaurusConfigMock;
    }

    /**
     * Get Thesaurus config factory
     *
     * @param MockObject|ThesaurusConfig $thesaurusConfig Thesaurus config.
     *
     * @return MockObject|ThesaurusConfigFactory
     */
    private function getThesaurusConfigFactoryMock($thesaurusConfig)
    {
        $thesaurusConfigFactory = $this->getMockBuilder(ThesaurusConfigFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $thesaurusConfigFactory->method('create')->willReturn($thesaurusConfig);

        return $thesaurusConfigFactory;
    }

    /**
     * Get Thesaurus cache config mock.
     *
     * @param bool $cacheStorageAllowed Whether cache storage of results is allowed
     *
     * @return MockObject|ThesaurusCacheConfig
     */
    private function getThesaurusCacheConfigMock($cacheStorageAllowed)
    {
        $thesaurusCacheConfig = $this->getMockBuilder(ThesaurusCacheConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $thesaurusCacheConfig->method('isCacheStorageAllowed')->willReturn($cacheStorageAllowed);

        return $thesaurusCacheConfig;
    }

    /**
     * Get Elasticsuite cache helper mock.
     *
     * @return MockObject|Cache
     */
    private function getCacheHelperMock()
    {
        return $this->getMockBuilder(Cache::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
