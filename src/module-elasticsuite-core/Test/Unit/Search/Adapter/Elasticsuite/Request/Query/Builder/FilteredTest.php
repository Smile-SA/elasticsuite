<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\Filtered as FilteredQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Filtered as FilteredQueryBuilder;

/**
 * Filtered search request query test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilteredTest extends AbstractComplexQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousFilteredQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery($this->getSubQueryMock('baseQuery'), $this->getSubQueryMock('filterQuery'));
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('filtered', $query);

        $this->assertArrayHasKey('query', $query['filtered']);
        $this->assertEquals('baseQuery', $query['filtered']['query']);

        $this->assertArrayHasKey('filter', $query['filtered']);
        $this->assertEquals('filterQuery', $query['filtered']['filter']);

        $this->assertEquals(FilteredQuery::DEFAULT_BOOST_VALUE, $query['filtered']['boost']);

        $this->assertArrayNotHasKey('_name', $query['filtered']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedFilteredQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $filteredQuery = new FilteredQuery($this->getSubQueryMock('baseQuery'), $this->getSubQueryMock('filterQuery'), 'queryName');
        $query = $builder->buildQuery($filteredQuery);

        $this->assertArrayHasKey('_name', $query['filtered']);
        $this->assertEquals('queryName', $query['filtered']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new FilteredQueryBuilder($this->getParentQueryBuilder());
    }
}
