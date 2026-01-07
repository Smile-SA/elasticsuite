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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Query\Fulltext;

use PHPUnit\Framework\MockObject\MockObject;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Helper\Text;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Index\Mapping;

/**
 * Fulltext query builder test case.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryBuilderTest extends \PHPUnit\Framework\TestCase
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
     * Test running the query builder using only correctly spelled terms.
     *
     * @return void
     */
    public function testExactSpellingQueryBuilder()
    {
        $this->runTestQueryBuilder('search text', SpellcheckerInterface::SPELLING_TYPE_EXACT, QueryInterface::TYPE_FILTER);
    }

    /**
     * Test running the query builder using mostly correctly spelled terms.
     *
     * @return void
     */
    public function testMostExactSpellingQueryBuilder()
    {
        $this->runTestQueryBuilder('search text', SpellcheckerInterface::SPELLING_TYPE_MOST_EXACT, QueryInterface::TYPE_FILTER);
    }

    /**
     * Test running the query builder using mostly mispelled terms.
     *
     * @return void
     */
    public function testMostFuzzySpellingQueryBuilder()
    {
        $this->runTestQueryBuilder('search text', SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY, QueryInterface::TYPE_BOOL);
    }

    /**
     * Test running the query builder using only mispelled terms.
     *
     * @return void
     */
    public function testFuzzySpellingQueryBuilder()
    {
        $this->runTestQueryBuilder('search text', SpellcheckerInterface::SPELLING_TYPE_FUZZY, QueryInterface::TYPE_BOOL);
    }

    /**
     * Test running the query builder using only stopwords.
     *
     * @return void
     */
    public function testPureStopWordsSpellingQueryBuilder()
    {
        $this->runTestQueryBuilder(
            'search text',
            SpellcheckerInterface::SPELLING_TYPE_PURE_STOPWORDS,
            QueryInterface::TYPE_MULTIMATCH
        );
    }

    /**
     * Test running the query builder using an array as search query.
     *
     * @return void
     */
    public function testMulipleSearchQueryBuilder()
    {
        $this->runTestQueryBuilder(['foo', 'bar'], SpellcheckerInterface::SPELLING_TYPE_EXACT, QueryInterface::TYPE_BOOL);
    }

    /**
     * Build a query and assert the query type is the expected one.
     *
     * @param string|string[] $searchTerms       Query search terms.
     * @param integer         $spellingType      Query spelling type.
     * @param string          $expectedQueryType Expected built query type.
     *
     * @return void
     */
    private function runTestQueryBuilder($searchTerms, $spellingType, $expectedQueryType)
    {
        $queryFactory    = $this->getQueryFactory($this->mockedQueryTypes);
        $fieldFilters    = $this->getFieldFilters();
        $containerConfig = $this->getContainerConfigMock($this->fields);
        $textHelper      = $this->getTextHelperMock();

        $builder = new QueryBuilder($queryFactory, $textHelper, $fieldFilters);

        $query = $builder->create($containerConfig, $searchTerms, $spellingType);

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertEquals($expectedQueryType, $query->getType());
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

        $mapping         = new Mapping('idField', $fields);
        $config->method('getMapping')->will($this->returnValue($mapping));

        $relevanceConfig = $this->getRelevanceConfig();
        $config->method('getRelevanceConfig')->will($this->returnValue($relevanceConfig));

        return $config;
    }

    /**
     * Get Elasticsuite text helper mock.
     *
     * @return MockObject|Text
     */
    private function getTextHelperMock()
    {
        return $this->getMockBuilder(Text::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the relevace configuration object used by the query builder.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getRelevanceConfig()
    {
        $relevanceConfig = $this->getMockBuilder(RelevanceConfigurationInterface::class)->getMock();
        $fuzzinessConfig = $this->getMockBuilder(FuzzinessConfigurationInterface::class)->getMock();

        $relevanceConfig->method('isFuzzinessEnabled')->will($this->returnValue(true));
        $relevanceConfig->method('isPhoneticSearchEnabled')->will($this->returnValue(true));
        $relevanceConfig->method('getFuzzinessConfiguration')->will($this->returnValue($fuzzinessConfig));

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
