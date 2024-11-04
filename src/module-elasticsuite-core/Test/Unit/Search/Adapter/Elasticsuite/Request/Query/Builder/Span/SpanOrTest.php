<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder\Span;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanOr as SpanOrQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanOr as SpanOrQuery;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * SpanOr search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanOrTest extends AbstractComplexSpanQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanOrQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanOrQuery();
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_or']);

        $this->assertEquals([], $query['span_or']['clauses']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_or']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanOrQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanOrQuery(
            [$this->getSubQueryMock('clause1'), $this->getSubQueryMock('clause2')],
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_or']);

        $this->assertEquals(['clause1', 'clause2'], $query['span_or']['clauses']);
        $this->assertEquals(17, $query['span_or']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanOrQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_or', $query);

        $this->assertArrayHasKey('clauses', $query['span_or']);
        $this->assertArrayHasKey('boost', $query['span_or']);
    }
}
