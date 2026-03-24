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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as AdapterQueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as RequestQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder as FilterQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder as FulltextQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Search adapter query builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test building a valid query.
     *
     * @return void
     */
    public function testBuildValidQuery()
    {
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue('queryType'));

        $this->assertEquals(['type' => 'queryType'], $this->getQueryBuilder()->buildQuery($query));
    }

    /**
     * Test the query builder throws an exception when using an invalid query type.
     *
     * @return void
     */
    public function testBuildInvalidQuery()
    {
        $this->expectExceptionMessage("Unknow query builder for invalidQueryType.");
        $this->expectException(\InvalidArgumentException::class);
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query->method('getType')->will($this->returnValue('invalidQueryType'));

        $this->getQueryBuilder()->buildQuery($query);
    }

    /**
     * Test that createQuery builds a filtered query using a string fulltext query and filters.
     *
     * Verifies that:
     * - createFulltextQuery() is called when query is a string
     * - createFilterQuery() is called when filters are provided
     * - QueryFactory::create() receives correct parameters
     *
     * @return void
     */
    public function testCreateQueryWithStringQueryAndFilters()
    {
        $containerConfiguration = $this->createMock(ContainerConfigurationInterface::class);

        $queryString = 'shoes';
        $filters = ['color' => 'red'];
        $spellingType = 'default';

        $fulltextQuery = $this->createStub(QueryInterface::class);
        $filterQuery = $this->createStub(QueryInterface::class);
        $expectedQuery = $this->createStub(QueryInterface::class);

        $queryFactory = $this->createMock(QueryFactory::class);

        $queryFactory->expects($this->once())
            ->method('create')
            ->with(
                QueryInterface::TYPE_FILTER,
                [
                    'query'  => $fulltextQuery,
                    'filter' => $filterQuery,
                ]
            )
            ->willReturn($expectedQuery);

        $builder = $this->getMockBuilder(RequestQueryBuilder::class)
            ->setConstructorArgs([
                $queryFactory,
                $this->createMock(FulltextQueryBuilder::class),
                $this->createMock(FilterQueryBuilder::class),
            ])
            ->onlyMethods(['createFulltextQuery', 'createFilterQuery'])
            ->getMock();

        $builder->expects($this->once())
            ->method('createFulltextQuery')
            ->with($containerConfiguration, $queryString, $spellingType)
            ->willReturn($fulltextQuery);

        $builder->expects($this->once())
            ->method('createFilterQuery')
            ->with($containerConfiguration, $filters)
            ->willReturn($filterQuery);

        $result = $builder->createQuery(
            $containerConfiguration,
            $queryString,
            $filters,
            $spellingType
        );

        $this->assertSame($expectedQuery, $result);
    }

    /**
     * Test that createQuery uses a provided QueryInterface instance directly
     * without attempting to build a fulltext query.
     *
     * This test verifies the execution path where the `$query` argument passed
     * to Builder::createQuery() is already an object implementing QueryInterface.
     * In this case the method should:
     *
     * - Detect that `$query` is an object.
     * - Assign it directly to the `query` parameter inside `$queryParams`.
     * - Skip the call to createFulltextQuery().
     * - Call QueryFactory::create() with QueryInterface::TYPE_FILTER.
     * - Pass the existing query object unchanged to the factory.
     * - Return the resulting QueryInterface instance created by the factory.
     *
     * This test specifically ensures coverage of the branch:
     *
     *     if (is_object($query)) {
     *         $queryParams['query'] = $query;
     *     }
     *
     * @return void
     */
    public function testCreateQueryWithQueryObject()
    {
        $containerConfiguration = $this->createMock(ContainerConfigurationInterface::class);

        $queryObject = $this->createStub(QueryInterface::class);
        $filters = [];
        $spellingType = 'default';

        $expectedQuery = $this->createStub(QueryInterface::class);

        $queryFactory = $this->createMock(QueryFactory::class);

        $queryFactory->expects($this->once())
            ->method('create')
            ->with(
                QueryInterface::TYPE_FILTER,
                ['query' => $queryObject]
            )
            ->willReturn($expectedQuery);

        $builder = new RequestQueryBuilder(
            $queryFactory,
            $this->createMock(FulltextQueryBuilder::class),
            $this->createMock(FilterQueryBuilder::class)
        );

        $result = $builder->createQuery(
            $containerConfiguration,
            $queryObject,
            $filters,
            $spellingType
        );

        $this->assertSame($expectedQuery, $result);
    }

    /**
     * Test that filters are properly created using the FilterQueryBuilder dependency.
     *
     * This test verifies that:
     * - The Builder delegates filter creation to the FilterQueryBuilder.
     * - The FilterQueryBuilder::create() method is called exactly once.
     * - The correct arguments are passed to the create() method.
     * - The resulting QueryInterface instance returned by FilterQueryBuilder
     *   is returned unchanged by Builder::createFilters().
     *
     * @return void
     */
    public function testCreateFiltersDelegatesToFilterQueryBuilder(): void
    {
        $filters = ['color' => 'red'];
        $containerConfiguration = $this->createMock(ContainerConfigurationInterface::class);
        $expectedQuery = $this->createStub(QueryInterface::class);

        $filterQueryBuilder = $this->createMock(FilterQueryBuilder::class);

        $filterQueryBuilder->expects($this->once())
            ->method('create')
            ->with($containerConfiguration, $filters)
            ->willReturn($expectedQuery);

        $builder = new RequestQueryBuilder(
            $this->createMock(QueryFactory::class),
            $this->createMock(FulltextQueryBuilder::class),
            $filterQueryBuilder
        );

        $result = $builder->createFilters($containerConfiguration, $filters);

        $this->assertSame($expectedQuery, $result);
    }

    /**
     * Mock a query builder.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder
     */
    private function getQueryBuilder()
    {
        $queryBuilderMock = $this->getMockBuilder(BuilderInterface::class)->getMock();

        $buildQueryCallback = function (QueryInterface $query) {
            return ['type' => $query->getType()];
        };

        $queryBuilderMock->method('buildQuery')->will($this->returnCallback($buildQueryCallback));

        return new AdapterQueryBuilder(['queryType' => $queryBuilderMock]);
    }
}
