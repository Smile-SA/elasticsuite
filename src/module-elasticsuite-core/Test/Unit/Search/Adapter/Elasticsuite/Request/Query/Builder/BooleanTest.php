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

use Smile\ElasticsuiteCore\Search\Request\Query\Boolean as BooleanQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Boolean as BooleanQueryBuilder;

/**
 * Boolean search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BooleanTest extends AbstractComplexQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testDefaultBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery();
        $query = $builder->buildQuery($boolQuery);

        $this->assertArrayHasKey('bool', $query);

        $this->assertEquals(BooleanQuery::DEFAULT_BOOST_VALUE, $query['bool']['boost']);
        $this->assertArrayNotHasKey('minimum_should_match', $query['bool']);

        $this->assertArrayHasKey('must', $query['bool']);
        $this->assertArrayHasKey('should', $query['bool']);
        $this->assertArrayHasKey('must_not', $query['bool']);

        $this->assertArrayNotHasKey('_name', $query['bool']);
        $this->assertArrayNotHasKey('_cache', $query['bool']);
    }

    /**
     * Pure must query builder test.
     *
     * @return void
     */
    public function testMustBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery([$this->getSubQueryMock('mustClause1'), $this->getSubQueryMock('mustClause2')]);
        $query = $builder->buildQuery($boolQuery);

        $this->assertCount(2, $query['bool']['must']);
        $this->assertArrayNotHasKey('minimum_should_match', $query['bool']);
        $this->assertContains('mustClause1', $query['bool']['must']);
        $this->assertContains('mustClause2', $query['bool']['must']);
    }

    /**
     * Pure should query builder test.
     *
     * @return void
     */
    public function testShouldBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery([], [$this->getSubQueryMock('shouldClause1'), $this->getSubQueryMock('shouldClause2')]);
        $query = $builder->buildQuery($boolQuery);

        $this->assertEquals(1, $query['bool']['minimum_should_match']);
        $this->assertCount(2, $query['bool']['should']);
        $this->assertContains('shouldClause1', $query['bool']['should']);
        $this->assertContains('shouldClause2', $query['bool']['should']);
    }

    /**
     * Pure must not query builder test.
     *
     * @return void
     */
    public function testMustNotBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery([], [], [$this->getSubQueryMock('mustNotClause1'), $this->getSubQueryMock('mustNotClause2')]);
        $query = $builder->buildQuery($boolQuery);

        $this->assertArrayNotHasKey('minimum_should_match', $query['bool']);
        $this->assertCount(2, $query['bool']['must_not']);
        $this->assertContains('mustNotClause1', $query['bool']['must_not']);
        $this->assertContains('mustNotClause2', $query['bool']['must_not']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery([], [], [], 1, 'queryName');
        $query = $builder->buildQuery($boolQuery);

        $this->assertArrayHasKey('_name', $query['bool']);
        $this->assertEquals('queryName', $query['bool']['_name']);
    }

    /**
     * Test the builder with a query named or renamed after creation.
     *
     * @return void
     */
    public function testLaterNamedBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery();
        $boolQuery->setName('queryName');
        $query = $builder->buildQuery($boolQuery);

        $this->assertArrayHasKey('_name', $query['bool']);
        $this->assertEquals('queryName', $query['bool']['_name']);
    }

    /**
     * Test the builder with mandatory + cache params.
     *
     * @return void
     */
    public function testCachedBooleanQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $boolQuery = new BooleanQuery([], [], [], 1, null, BooleanQuery::DEFAULT_BOOST_VALUE, true);
        $query = $builder->buildQuery($boolQuery);

        $this->assertArrayHasKey('_cache', $query['bool']);
        $this->assertEquals(true, $query['bool']['_cache']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new BooleanQueryBuilder($this->getParentQueryBuilder());
    }
}
