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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\Filtered as FilteredQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Filtered as FilteredQueryBuilder;

/**
 * Filtered search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilteredTest extends AbstractComplexQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousFilteredQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery(
            $this->getSubQueryMock('baseQuery'),
            $this->getSubQueryMock('filterQuery')
        );
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('bool', $query);

        $this->assertArrayHasKey('must', $query['bool']);
        $this->assertEquals('baseQuery', $query['bool']['must']);

        $this->assertArrayHasKey('filter', $query['bool']);
        $this->assertEquals('filterQuery', $query['bool']['filter']);

        $this->assertEquals(FilteredQuery::DEFAULT_BOOST_VALUE, $query['bool']['boost']);

        $this->assertArrayNotHasKey('_name', $query['bool']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedFilteredQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery(
            $this->getSubQueryMock('baseQuery'),
            $this->getSubQueryMock('filterQuery'),
            'queryName'
        );
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('_name', $query['bool']);
        $this->assertEquals('queryName', $query['bool']['_name']);
    }

    /**
     * Test the builder with a filter query but an empty base query.
     *
     * @return void
     */
    public function testQueryLessFilteredQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery(
            null,
            $this->getSubQueryMock('filterQuery')
        );
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('constant_score', $query);

        $this->assertArrayHasKey('filter', $query['constant_score']);
        $this->assertEquals('filterQuery', $query['constant_score']['filter']);
    }

    /**
     * Test the builder with an empty filter query on an empty base query.
     *
     * @return void
     */
    public function testEmptyFilteredQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery();
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('constant_score', $query);

        $this->assertArrayHasKey('filter', $query['constant_score']);
        $this->assertEquals(['match_all' => new \stdClass()], $query['constant_score']['filter']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new FilteredQueryBuilder($this->getParentQueryBuilder());
    }
}
