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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanNot as SpanNotQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanNot as SpanNotQuery;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * SpanNot search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanNotTest extends AbstractComplexSpanQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanNotQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanNotQuery(
            $this->getSubQueryMock('includeQuery'),
            $this->getSubQueryMock('excludeQuery')
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_not']);

        $this->assertEquals('includeQuery', $query['span_not']['include']);
        $this->assertEquals('excludeQuery', $query['span_not']['exclude']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_not']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanNotQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanNotQuery(
            $this->getSubQueryMock('includeQuery'),
            $this->getSubQueryMock('excludeQuery'),
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_not']);

        $this->assertEquals('includeQuery', $query['span_not']['include']);
        $this->assertEquals('excludeQuery', $query['span_not']['exclude']);
        $this->assertEquals(17, $query['span_not']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanNotQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_not', $query);

        $this->assertArrayHasKey('include', $query['span_not']);
        $this->assertArrayHasKey('exclude', $query['span_not']);
        $this->assertArrayHasKey('boost', $query['span_not']);
    }
}
