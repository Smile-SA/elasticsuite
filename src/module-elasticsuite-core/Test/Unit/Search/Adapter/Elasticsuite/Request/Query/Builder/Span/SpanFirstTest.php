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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanFirst as SpanFirstQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanFirst as SpanFirstQuery;

/**
 * SpanFirst search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanFirstTest extends AbstractComplexSpanQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanFirstQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanFirstQuery(
            $this->getSubQueryMock('matchSpanQuery'),
            '3'
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_first']);

        $this->assertEquals('matchSpanQuery', $query['span_first']['match']);
        $this->assertEquals('3', $query['span_first']['end']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_first']['boost']);
    }

    /**
     * Test the builder with mandatory params only.
     */
    public function testNamedSpanFirstQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanFirstQuery(
            $this->getSubQueryMock('matchSpanQuery'),
            '3',
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayHasKey('_name', $query['span_first']);

        $this->assertEquals('matchSpanQuery', $query['span_first']['match']);
        $this->assertEquals('3', $query['span_first']['end']);
        $this->assertEquals(17, $query['span_first']['boost']);
        $this->assertEquals('queryName', $query['span_first']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanFirstQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_first', $query);

        $this->assertArrayHasKey('boost', $query['span_first']);
        $this->assertArrayHasKey('match', $query['span_first']);
        $this->assertArrayHasKey('end', $query['span_first']);
    }
}
