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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanContaining as SpanContainingQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanContaining as SpanContainingQuery;

/**
 * SpanContaining search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanContainingTest extends AbstractComplexSpanQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanContainingQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanContainingQuery(
            $this->getSubQueryMock('bigQuery'),
            $this->getSubQueryMock('littleQuery')
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_containing']);

        $this->assertEquals('bigQuery', $query['span_containing']['big']);
        $this->assertEquals('littleQuery', $query['span_containing']['little']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_containing']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanContainingQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanContainingQuery(
            $this->getSubQueryMock('bigQuery'),
            $this->getSubQueryMock('littleQuery'),
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_containing']);

        $this->assertEquals('bigQuery', $query['span_containing']['big']);
        $this->assertEquals('littleQuery', $query['span_containing']['little']);
        $this->assertEquals(17, $query['span_containing']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanContainingQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_containing', $query);

        $this->assertArrayHasKey('boost', $query['span_containing']);
        $this->assertArrayHasKey('little', $query['span_containing']);
        $this->assertArrayHasKey('big', $query['span_containing']);
    }
}
