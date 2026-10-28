<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Plugin;

use Magento\Framework\Interception\DefinitionInterface;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterface as SpellcheckRequestInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Helper\Text;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder;
use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;
use Smile\ElasticsuiteThesaurus\Plugin\QueryRewrite;
use Smile\ElasticsuiteThesaurus\Test\Unit\FulltextQueryBuilderInterceptor;

/**
 * QueryRewrite plugin test case.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryRewriteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $mockedQueryTypes = [
        QueryInterface::TYPE_COMMON,
        QueryInterface::TYPE_MULTIMATCH,
        QueryInterface::TYPE_FILTER,
        QueryInterface::TYPE_BOOL,
    ];

    /**
     * @var array
     */
    private static $fields = [];

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::$fields = [
            new Field('idField', Field::FIELD_TYPE_INTEGER),
            new Field('fulltextSearch1', Field::FIELD_TYPE_TEXT, null, ['is_searchable' => true]),
            new Field('fulltextSearch2', Field::FIELD_TYPE_TEXT, null, ['is_searchable' => true, 'is_filterable' => false]),
            new Field('fulltextSearch3', Field::FIELD_TYPE_TEXT, null, ['is_searchable' => true, 'is_used_in_spellcheck' => true]),
        ];
    }

    /**
     * Test running the query builder using an array as search query while investigating the depth parameter,
     * while the thesaurus index does not provide any rewrite.
     *
     * @return void
     */
    public function testMultipleSearchQueryDepthBuilder()
    {
        $queryFactory = $this->getQueryFactory($this->mockedQueryTypes);
        $containerConfig = $this->getContainerConfigMock(self::$fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 0;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);
        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        $textHelper = $this->getRealTextHelper();

        $queryRewritePlugin = new QueryRewrite(
            $queryFactory,
            $thesaurusConfigFactory,
            $thesaurusIndex,
            $textHelper,
            $this->getSpellcheckRequestFactoryMock(),
            $this->getMockBuilder(SpellcheckerInterface::class)->getMock()
        );
        $queryBuilderInterceptor = $this->getQueryBuilderWithPlugin($queryFactory, $queryRewritePlugin);

        /*
         * Testing that getQueryRewrites is not called again from the "$proceed",
         * ie when the queryBuilder calls itself once for every search terms of the provided array.
         */
        $thesaurusIndex->method('getQueryRewrites')->willReturn([]);
        /*
         * withConsecutive removed in PHPUnit 10 without any alternative \o/.
        $thesaurusIndex->expects($this->exactly(2))->method('getQueryRewrites')->withConsecutive(
            [$containerConfig, 'foo', 1],
            [$containerConfig, 'bar', 1],
        );
        */
        $invokeCount = $this->exactly(2);
        $numberOfInvocationsCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $numberOfInvocationsCallback = 'getInvocationCount';
        }
        $thesaurusIndex->expects($invokeCount)->method('getQueryRewrites')->willReturnCallback(
            function (...$expectedInputParameters) use ($invokeCount, $containerConfig, $numberOfInvocationsCallback) {
                if ($invokeCount->$numberOfInvocationsCallback() === 1) {
                    $this->assertEquals($containerConfig, $expectedInputParameters[0]);
                    $this->assertEquals('foo', $expectedInputParameters[1]);
                    $this->assertEquals(1, $expectedInputParameters[2]);
                }
                if ($invokeCount->$numberOfInvocationsCallback() === 2) {
                    $this->assertEquals($containerConfig, $expectedInputParameters[0]);
                    $this->assertEquals('bar', $expectedInputParameters[1]);
                    $this->assertEquals(1, $expectedInputParameters[2]);
                }
            }
        );
        $initialQuery = $queryBuilderInterceptor->create($containerConfig, ['foo', 'bar'], $spellingType);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $initialQuery->getType());

        /*
         * Testing that the cache works as intended.
         * Please note: do NOT do any further test on thesaurusIndex/getQueryRewrites, it seems the 'expects/never'
         * cannot be overwritten by a newer 'expects' condition.
         */
        $thesaurusIndex->expects($this->never())->method('getQueryRewrites');
        $secondQuery = $queryBuilderInterceptor->create($containerConfig, ['foo', 'bar'], $spellingType);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $secondQuery->getType());
    }

    /**
     * Test running the query builder using an array as search query while investigating the depth parameter,
     * while the thesaurus index provides rewrites.
     *
     * @return void
     */
    public function testMultipleSearchQueryDepthBuilderWithRewrites()
    {
        $queryFactory = $this->getQueryFactory($this->mockedQueryTypes);
        $containerConfig = $this->getContainerConfigMock(self::$fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 0;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);
        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        $textHelper = $this->getRealTextHelper();

        $queryRewritePlugin = new QueryRewrite(
            $queryFactory,
            $thesaurusConfigFactory,
            $thesaurusIndex,
            $textHelper,
            $this->getSpellcheckRequestFactoryMock(),
            $this->getMockBuilder(SpellcheckerInterface::class)->getMock()
        );
        $queryBuilderInterceptor = $this->getQueryBuilderWithPlugin($queryFactory, $queryRewritePlugin);

        /*
         * withConsecutive removed in PHPUnit 10 without any alternative \o/.
         * ----
        $thesaurusIndex->expects($this->exactly(2))->method('getQueryRewrites')->withConsecutive(
            [$containerConfig, 'foo', 1],
            [$containerConfig, 'bar', 1],
        */
        $invokeCount = $this->exactly(2);
        $numberOfInvocationsCallback = 'numberOfInvocations';
        if (method_exists($invokeCount, 'getInvocationCount')) {
            // Method 'numberOfInvocations' only exists starting from PHPUnit 10.
            $numberOfInvocationsCallback = 'getInvocationCount';
        }
        $thesaurusIndex->expects($invokeCount)->method('getQueryRewrites')->willReturnCallback(
            function (...$expectedInputParameters) use ($invokeCount, $containerConfig, $numberOfInvocationsCallback) {
                if ($invokeCount->$numberOfInvocationsCallback() === 1) {
                    $this->assertEquals($containerConfig, $expectedInputParameters[0]);
                    $this->assertEquals('foo', $expectedInputParameters[1]);
                    $this->assertEquals(1, $expectedInputParameters[2]);

                    return  ['foo bar' => 0.1];
                }
                if ($invokeCount->$numberOfInvocationsCallback() === 2) {
                    $this->assertEquals($containerConfig, $expectedInputParameters[0]);
                    $this->assertEquals('bar', $expectedInputParameters[1]);
                    $this->assertEquals(1, $expectedInputParameters[2]);

                    return ['bar fight' => 0.1];
                }

                return [];
            }
        );
        $query = $queryBuilderInterceptor->create($containerConfig, ['foo', 'bar'], $spellingType);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $query->getType());
    }

    /**
     * Test running the query builder using a single search expression and application of rewrites limitation
     * per search term  while the thesaurus index provides all rewrites.
     *
     * @return void
     */
    public function testSingleSearchQueryLimitedRewrites()
    {
        $queryFactory = $this->getQueryFactory($this->mockedQueryTypes);
        $queryFactoryFullMock = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerConfig = $this->getContainerConfigMock(self::$fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 1;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);
        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        $textHelper = $this->getRealTextHelper();

        // Passing the mock Query Factory to the plugin to count the occurence of calls to 'create'.
        $queryRewritePlugin = new QueryRewrite(
            $queryFactoryFullMock,
            $thesaurusConfigFactory,
            $thesaurusIndex,
            $textHelper,
            $this->getSpellcheckRequestFactoryMock(),
            $this->getMockBuilder(SpellcheckerInterface::class)->getMock()
        );
        // But passing the real Query Factory (with mocked factories) to the query builder itself.
        $queryBuilderInterceptor = $this->getQueryBuilderWithPlugin($queryFactory, $queryRewritePlugin);

        $thesaurusIndex->expects($this->exactly(1))->method('getQueryRewrites')->willReturnMap(
            [
                [$containerConfig, 'foo', 1, ['foo bar' => 0.1, 'foo light' => 0.1, 'moo' => 0.1, 'moo bar' => 0.01]],
            ]
        );

        $queryFactoryFullMock->expects($this->exactly(1))->method('create')->with(
            $this->equalTo(QueryInterface::TYPE_BOOL),
            $this->callback(
                function ($createArguments) use ($maxRewrittenQueries) {
                    if (!is_array($createArguments)
                        || count($createArguments) > 1
                        || !array_key_exists('should', $createArguments)
                        || !is_array($createArguments['should'])
                    ) {
                        return false;
                    }
                    $queries = $createArguments['should'];
                    // The initial query needs to be counted.
                    if (count($queries) > (1 + $maxRewrittenQueries)) {
                        return false;
                    }
                    foreach ($queries as $query) {
                        if (false == ($query instanceof QueryInterface)) {
                            return false;
                        }
                        /** @var QueryInterface $query */
                        if ($query->getType() !== QueryInterface::TYPE_FILTER) {
                            return false;
                        }
                    }

                    return true;
                }
            )
        );

        /** @var \Smile\ElasticsuiteCore\Search\Request\Query\Boolean $query */
        $query = $queryBuilderInterceptor->create($containerConfig, 'foo', $spellingType);
    }

    /**
     * Test the spelling types used for the original query and for the rewrites.
     *
     * @dataProvider spellingTypesDataProvider
     *
     * @param string|array $queryText                 Query text.
     * @param int          $spellingType              Spelling type of the original query.
     * @param array        $rewrites                  Rewrites as ['rewritten query' => spelling type returned by the spellchecker].
     * @param int          $expectedOriginalSpelling  Expected spelling type of the original query.
     * @param int[]        $expectedRewritesSpellings Expected spelling types of the rewrites, in order.
     * @param int          $expectedSpellcheckCount   Expected number of spellcheck calls.
     *
     * @return void
     */
    public function testSpellingTypes(
        $queryText,
        $spellingType,
        $rewrites,
        $expectedOriginalSpelling,
        $expectedRewritesSpellings,
        $expectedSpellcheckCount
    ) {
        $spellchecker = $this->getMockBuilder(SpellcheckerInterface::class)->getMock();
        $spellchecker->expects($this->exactly($expectedSpellcheckCount))
            ->method('getSpellingType')
            ->willReturnCallback(
                function ($request) use ($rewrites) {
                    return $rewrites[$request->getQueryText()];
                }
            );

        $spellingTypes = $this->getSpellingTypesUsedByPlugin($queryText, $spellingType, array_keys($rewrites), $spellchecker);

        // First call is the original query, the next ones are the rewrites.
        $this->assertSame($expectedOriginalSpelling, array_shift($spellingTypes));
        $this->assertSame($expectedRewritesSpellings, $spellingTypes);
    }

    /**
     * Spelling types data provider.
     *
     * @return array
     */
    public static function spellingTypesDataProvider()
    {
        $exact     = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $mostExact = SpellcheckerInterface::SPELLING_TYPE_MOST_EXACT;
        $mostFuzzy = SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY;
        $fuzzy     = SpellcheckerInterface::SPELLING_TYPE_FUZZY;
        $stopwords = SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS;

        return [
            // [queryText, spellingType, rewrites, expected original spelling, expected rewrites spellings, spellcheck calls].
            'better rewrite'            => ['fitness shirts', $mostFuzzy, ['fitness tee' => $exact], $exact, [$mostFuzzy], 1],
            'better rewrite, fuzzy'     => ['finess shirts', $fuzzy, ['finess tee' => $mostFuzzy], $exact, [$mostFuzzy], 1],
            'better rewrite, one word'  => ['shirts', $mostFuzzy, ['tee' => $exact], $exact, [$exact], 1],
            'only first rewrite probed' => [
                'fitness shirts',
                $fuzzy,
                ['fitness tee' => $exact, 'fitness top' => $fuzzy, 'fitness tank' => $fuzzy],
                $exact,
                [$mostFuzzy, $mostFuzzy, $mostFuzzy],
                1,
            ],
            'same spelling'             => ['finess tank', $mostFuzzy, ['finess tee' => $mostFuzzy], $mostFuzzy, [$mostFuzzy], 1],
            'worse rewrite'             => ['sliders now', $mostExact, ['slides now' => $fuzzy], $mostExact, [$mostExact], 1],
            'original exact'            => ['fitness shirts', $exact, ['fitness tee' => $fuzzy], $exact, [$exact], 0],
            'original pure stopwords'   => ['the of', $stopwords, ['a of' => $fuzzy], $stopwords, [$mostExact], 0],
            'array of queries'          => [['foo', 'bar'], $exact, ['foo bar' => $fuzzy], $exact, [$exact], 0],
        ];
    }

    /**
     * Run the plugin and get the spelling types it passes to the query builder: original query first, then the rewrites.
     *
     * @param string|array          $queryText    Query text.
     * @param int                   $spellingType Spelling type of the original query.
     * @param string[]              $rewrites     Rewritten queries.
     * @param SpellcheckerInterface $spellchecker Spellchecker.
     *
     * @return int[]
     */
    private function getSpellingTypesUsedByPlugin($queryText, $spellingType, $rewrites, $spellchecker)
    {
        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        $thesaurusIndex->method('getQueryRewrites')->willReturn(array_fill_keys($rewrites, 0.1));

        $queryRewritePlugin = new QueryRewrite(
            $this->getQueryFactory($this->mockedQueryTypes),
            $this->getThesaurusConfigFactoryMock(0),
            $thesaurusIndex,
            $this->getRealTextHelper(),
            $this->getSpellcheckRequestFactoryMock(),
            $spellchecker
        );

        $spellingTypes = [];
        $proceed       = function ($containerConfig, $queryText, $spellingType) use (&$spellingTypes) {
            $spellingTypes[] = $spellingType;

            return $this->getMockBuilder(QueryInterface::class)->getMock();
        };

        $queryRewritePlugin->aroundCreate(
            $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock(),
            $proceed,
            $this->getContainerConfigMock(self::$fields),
            $queryText,
            $spellingType
        );

        return $spellingTypes;
    }

    /**
     * Mock the spellcheck request factory: the created request only exposes the query text.
     *
     * @return MockObject
     */
    private function getSpellcheckRequestFactoryMock()
    {
        $factory = $this->getMockBuilder(SpellcheckRequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturnCallback(
            function (array $data) {
                $request = $this->getMockBuilder(SpellcheckRequestInterface::class)->getMock();
                $request->method('getQueryText')->willReturn($data['queryText']);

                return $request;
            }
        );

        return $factory;
    }

    /**
     * Get a fulltext query builder with a configured query rewrite plugin.
     *
     * @param QueryFactory $queryFactory       Query Factory.
     * @param QueryRewrite $queryRewritePlugin Fulltext query rewrite plugin.
     *
     * @return Interceptor
     */
    private function getQueryBuilderWithPlugin($queryFactory, $queryRewritePlugin)
    {
        $fieldFilters = $this->getFieldFilters();
        $textHelper   = $this->getRealTextHelper();

        $pluginList = $this->getMockBuilder(Pluginlist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pluginList->method('getNext')->willReturnMap(
            [
                [Builder::class, 'create', '__self', [DefinitionInterface::LISTENER_AROUND => 'queryRewriteSynonyms']],
                [Builder::class, 'create', 'queryRewriteSynonyms', null],
            ]
        );
        $pluginList->method('getPlugin')->willReturnMap(
            [
                [Builder::class, 'queryRewriteSynonyms', $queryRewritePlugin],
            ]
        );

        $queryBuilderInterceptor = $this->getMockBuilder(FulltextQueryBuilderInterceptor::class)
            ->setConstructorArgs([$queryFactory, $textHelper, $fieldFilters])
            ->onlyMethods(['___init'])
            ->getMock();

        $queryBuilderReflection = new \ReflectionClass(FulltextQueryBuilderInterceptor::class);
        $subjectTypeProperty = $queryBuilderReflection->getProperty('subjectType');
        $subjectTypeProperty->setAccessible(true);
        $subjectTypeProperty->setValue($queryBuilderInterceptor, Builder::class);

        $pluginListProperty = $queryBuilderReflection->getProperty('pluginList');
        $pluginListProperty->setAccessible(true);
        $pluginListProperty->setValue($queryBuilderInterceptor, $pluginList);

        return $queryBuilderInterceptor;
    }

    /**
     * Mock the query factory used by the builder.
     *
     * @param string[] $queryTypes Mocked query types.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private function getQueryFactory($queryTypes)
    {
        $factories = [];

        foreach ($queryTypes as $currentType) {
            $queryMock = $this->getMockBuilder(QueryInterface::class)->getMock();
            $queryMock->method('getType')->willReturn($currentType);

            $factory = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
            $factory->method('create')->willReturn($queryMock);

            $factories[$currentType] = $factory;
        }

        return new QueryFactory($factories);
    }

    /**
     * Mock the thesaurus config factory.
     *
     * @param int $maxRewrittenQueries Max Rewritten Queries.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getThesaurusConfigFactoryMock($maxRewrittenQueries)
    {
        $thesaurusConfig = $this->getMockBuilder(ThesaurusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $thesaurusConfig->method('getMaxRewrittenQueries')->willReturn($maxRewrittenQueries);

        $thesaurusConfigFactory = $this->getMockBuilder(ThesaurusConfigFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $thesaurusConfigFactory->method('create')->willReturn($thesaurusConfig);

        return $thesaurusConfigFactory;
    }

    /**
     * Mock the configuration used by the query builder.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[] $fields Mapping fields.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getContainerConfigMock($fields)
    {
        $config = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->getMock();

        $mapping = new Mapping('idField', $fields);
        $config->method('getMapping')->willReturn($mapping);

        $relevanceConfig = $this->getRelevanceConfig();
        $config->method('getRelevanceConfig')->willReturn($relevanceConfig);

        return $config;
    }

    /**
     * Get Elasticsuite text helper mock.
     *
     * @return Text
     */
    private function getRealTextHelper()
    {
        return new Text();
    }

    /**
     * Mock the relevace configuration object used by the query builder.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getRelevanceConfig()
    {
        $relevanceConfig = $this->getMockBuilder(RelevanceConfigurationInterface::class)
            ->getMock();

        $relevanceConfig->method('isFuzzinessEnabled')->willReturn(true);
        $relevanceConfig->method('isPhoneticSearchEnabled')->willReturn(true);

        return $relevanceConfig;
    }

    /**
     * Prepare field filters used to retrieve weighted search properties during search.
     *
     * @return FieldFilterInterface[]
     */
    private function getFieldFilters()
    {
        $fieldFilterMock = $this->getMockBuilder(FieldFilterInterface::class)->getMock();

        $fieldFilterMock->method('filterField')->willReturn(true);

        return [
            'searchableFieldFilter'       => $fieldFilterMock,
            'fuzzyFieldFilter'            => $fieldFilterMock,
            'nonStandardFuzzyFieldFilter' => $fieldFilterMock,
        ];
    }
}
