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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder as AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder\Builder as SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Collapse\Builder as CollapseBuilder;
use Smile\ElasticsuiteCore\Search\Request;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\CollapseInterface;

/**
 * Search adapter query mapper test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test mapping a base query.
     *
     * @return void
     */
    public function testBaseQueryMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();
        $searchRequest = new Request('requestName', 'indexName', $query, null, null, 0, 1);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertEquals(0, $mappedRequest['from']);
        $this->assertEquals(1, $mappedRequest['size']);
        $this->assertEquals([], $mappedRequest['sort']);
        $this->assertEquals('query', $mappedRequest['query']);
    }

    /**
     * Test mapping a query using a filter.
     *
     * @return void
     */
    public function testFilteredQueryMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();
        $filter  = $this->getMockBuilder(QueryInterface::class)->getMock();
        $searchRequest = new Request('requestName', 'indexName', $query, $filter);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertEquals('query', $mappedRequest['post_filter']);
    }

    /**
     * Test aggregations mapping.
     *
     * @return void
     */
    public function testAggregationsMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, null, 0, 1, [], ['agg' => 'agg']);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertEquals('aggregations', $mappedRequest['aggregations']);
    }

    /**
     * Test sort orders mapping.
     *
     * @return void
     */
    public function testSortOrdersMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, ['sort' => 'sort'], 0, 10);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertEquals('sortOrders', $mappedRequest['sort']);
    }

    /**
     * Test request mapping in the absence of a collapse section.
     *
     * @return void
     */
    public function testNoCollapseMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, ['sort' => 'sort'], 0, 10);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertNotContains('collapse', $mappedRequest);
    }

    /**
     * Test request mapping in the presence of a collapse section.
     *
     * @return void
     */
    public function testCollapseMapping()
    {
        $mapper  = $this->getMapper();
        $query   = $this->getMockBuilder(QueryInterface::class)->getMock();

        $searchRequest = new Request('requestName', 'indexName', $query, null, ['sort' => 'sort'], 0, 10);

        $collapse = $this->getMockBuilder(CollapseInterface::class)->getMock();
        $searchRequest->setCollapse($collapse);

        $mappedRequest = $mapper->buildSearchRequest($searchRequest);

        $this->assertContains('collapse', $mappedRequest);
        $this->assertEquals('collapse', $mappedRequest['collapse']);
    }

    /**
     * Prepare the search request mapper used during tests.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Mapper
     */
    private function getMapper()
    {
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $queryBuilderMock->method('buildQuery')->will($this->returnValue('query'));

        $sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)->disableOriginalConstructor()->getMock();
        $sortOrderBuilderMock->method('buildSortOrders')->will($this->returnValue('sortOrders'));

        $aggregationBuilderMock = $this->getMockBuilder(AggregationBuilder::class)->disableOriginalConstructor()->getMock();
        $aggregationBuilderMock->method('buildAggregations')->will($this->returnValue('aggregations'));

        $collapseBuilderMock = $this->getMockBuilder(CollapseBuilder::class)->disableOriginalConstructor()->getMock();
        $collapseBuilderMock->method('buildCollapse')->will($this->returnValue('collapse'));

        return new Mapper($queryBuilderMock, $sortOrderBuilderMock, $aggregationBuilderMock, $collapseBuilderMock);
    }
}
