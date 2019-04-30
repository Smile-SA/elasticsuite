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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Query\Filter;

use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder;
use Smile\ElasticsuiteCore\Index\Mapping\Field;

/**
 * Filter query builder test case.
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
        \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_TERMS,
        \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_RANGE,
        \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_MATCH,
        \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_BOOL,
        \Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_NESTED,
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
            new Field('idField', 'integer'),
            new Field('simpleTextField', Field::FIELD_TYPE_KEYWORD),
            new Field('analyzedField', Field::FIELD_TYPE_TEXT, null, ['is_searchable' => true, 'is_filterable' => false]),
            new Field('nested.child', Field::FIELD_TYPE_KEYWORD, 'nested'),
        ];
    }

    /**
     * Test simple eq filter on the id field.
     *
     * @return void
     */
    public function testSingleQueryFilter()
    {
        $query = $this->buildQuery(['simpleTextField' => 'filterValue']);
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_TERMS, $query->getType());

        $query = $this->buildQuery(['simpleTextField' => ['filterValue1', 'filterValue2']]);
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_TERMS, $query->getType());
    }

    /**
     * Test multiple fields query filter.
     *
     * @return void
     */
    public function testMultipleQueryFilter()
    {
        $query = $this->buildQuery(['simpleTextField' => 'filterValue', 'idField' => 1]);

        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_BOOL, $query->getType());
    }

    /**
     * Test range query conditions.
     *
     * @return void
     */
    public function testRangeQueryFilters()
    {
        $rangeConditions = ['from', 'to', 'lteq', 'lte', 'lt', 'gteq', 'gte', 'moreq', 'gt'];
        foreach ($rangeConditions as $condition) {
            $query = $this->buildQuery(['idField' => [$condition => 1]]);
            $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
            $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_RANGE, $query->getType());
        }
    }

    /**
     * Test fulltext query condiotions.
     *
     * @return void
     */
    public function testFulltextQueryFilter()
    {
        $query = $this->buildQuery(['simpleTextField' => ['like' => 'fulltext']]);
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_TERMS, $query->getType());

        $query = $this->buildQuery(['analyzedField' => ['like' => 'fulltext']]);
        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_MATCH, $query->getType());
    }

    /**
     * Test using a raw query as condition.
     *
     * @return void
     */
    public function testRawQueryFilter()
    {
        $query       = $this->getMockBuilder(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class)->getMock();
        $queryFilter = $this->buildQuery(['simpleTextField' => $query]);

        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $queryFilter);
    }

    /**
     * Test conditions on nested fields.
     *
     * @return void
     */
    public function testNestedFieldFilter()
    {
        $query = $this->buildQuery(['nested.child' => 'filterValue']);

        $this->assertInstanceOf(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class, $query);
        $this->assertEquals(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::TYPE_NESTED, $query->getType());
    }

    /**
     * Test using an not supported exception throws an exception.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Condition regexp is not supported.
     *
     * @return void
     */
    public function testUnsupportedCondition()
    {
        $this->buildQuery(['simpleTextField' => ['regexp' => 'filterValue']]);
    }

    /**
     * Generate a query from conditions using mocked objects.
     *
     * @param array $conditions Conditions.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function buildQuery($conditions)
    {
        $builder = new QueryBuilder($this->getQueryFactory($this->mockedQueryTypes));
        $config  = $this->getContainerConfigMock($this->fields);

        return $builder->create($config, $conditions);
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
            $queryMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Search\Request\QueryInterface::class)->getMock();
            $queryMock->method('getType')->will($this->returnValue($currentType));

            $factory = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)->getMock();
            $factory->method('create')->will($this->returnValue($queryMock));

            $factories[$currentType] = $factory;
        }

        return new \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory($factories);
    }

    /**
     * Mock the configuration used by the query builder.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[] $fields Mapping fields.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getContainerConfigMock($fields)
    {
        $config = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapping = new \Smile\ElasticsuiteCore\Index\Mapping('idField', $fields);
        $config->method('getMapping')->will($this->returnValue($mapping));

        return $config;
    }
}
