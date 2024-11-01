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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanNear as SpanNearQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanNear as SpanNearQuery;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * SpanNear search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanNearTest extends AbstractComplexSpanQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanNearQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanNearQuery();
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_near']);

        $this->assertEquals([], $query['span_near']['clauses']);
        $this->assertEquals(12, $query['span_near']['slop']);
        $this->assertEquals(true, $query['span_near']['in_order']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_near']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanNearQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanNearQuery(
            [$this->getSubQueryMock('clause1'), $this->getSubQueryMock('clause2')],
            5,
            false,
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_near']);

        $this->assertEquals(['clause1', 'clause2'], $query['span_near']['clauses']);
        $this->assertEquals(5, $query['span_near']['slop']);
        $this->assertEquals(false, $query['span_near']['in_order']);
        $this->assertEquals(17, $query['span_near']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanNearQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_near', $query);

        $this->assertArrayHasKey('clauses', $query['span_near']);
        $this->assertArrayHasKey('slop', $query['span_near']);
        $this->assertArrayHasKey('in_order', $query['span_near']);
        $this->assertArrayHasKey('boost', $query['span_near']);
    }
}
