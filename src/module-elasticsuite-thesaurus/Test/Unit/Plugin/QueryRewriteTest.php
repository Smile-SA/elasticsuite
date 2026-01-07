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
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Helper\Text;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder;
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
    private $fields = [];

    /**
     * Constructor.
     *
     * @param string $name     Test case name.
     * @param array  $data     Test case data.
     * @param string $dataName Test case data name.
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->fields = [
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
        $containerConfig = $this->getContainerConfigMock($this->fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 0;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);

        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryRewritePlugin = new QueryRewrite($queryFactory, $thesaurusConfigFactory, $thesaurusIndex);
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
        $containerConfig = $this->getContainerConfigMock($this->fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 0;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);

        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryRewritePlugin = new QueryRewrite($queryFactory, $thesaurusConfigFactory, $thesaurusIndex);
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
        $containerConfig = $this->getContainerConfigMock($this->fields);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;
        $maxRewrittenQueries = 1;

        $thesaurusConfigFactory = $this->getThesaurusConfigFactoryMock($maxRewrittenQueries);

        $thesaurusIndex = $this->getMockBuilder(ThesaurusIndex::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Passing the mock Query Factory to the plugin to count the occurrence of calls to 'create'.
        $queryRewritePlugin = new QueryRewrite($queryFactoryFullMock, $thesaurusConfigFactory, $thesaurusIndex);
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
        $pluginList->method('getNext')->will($this->returnValueMap(
            [
                [Builder::class, 'create', '__self', [DefinitionInterface::LISTENER_AROUND => 'queryRewriteSynonyms']],
                [Builder::class, 'create', 'queryRewriteSynonyms', null],
            ]
        ));
        $pluginList->method('getPlugin')->will($this->returnValueMap(
            [
                [Builder::class, 'queryRewriteSynonyms', $queryRewritePlugin],
            ]
        ));

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
            $queryMock->method('getType')->will($this->returnValue($currentType));

            $factory = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
            $factory->method('create')->will($this->returnValue($queryMock));

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
        $thesaurusConfig->method('getMaxRewrittenQueries')->will($this->returnValue($maxRewrittenQueries));

        $thesaurusConfigFactory = $this->getMockBuilder(ThesaurusConfigFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $thesaurusConfigFactory->method('create')->will($this->returnValue($thesaurusConfig));

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
        $config->method('getMapping')->will($this->returnValue($mapping));

        $relevanceConfig = $this->getRelevanceConfig();
        $config->method('getRelevanceConfig')->will($this->returnValue($relevanceConfig));

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

        $relevanceConfig->method('isFuzzinessEnabled')->will($this->returnValue(true));
        $relevanceConfig->method('isPhoneticSearchEnabled')->will($this->returnValue(true));

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

        $fieldFilterMock->method('filterField')->will($this->returnValue(true));

        return [
            'searchableFieldFilter'       => $fieldFilterMock,
            'fuzzyFieldFilter'            => $fieldFilterMock,
            'nonStandardFuzzyFieldFilter' => $fieldFilterMock,
        ];
    }
}
