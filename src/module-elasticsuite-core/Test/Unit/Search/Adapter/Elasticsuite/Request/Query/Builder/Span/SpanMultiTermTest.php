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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanMultiTerm as SpanMultiTermQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanMultiTerm as SpanMultiTermQuery;

/**
 * SpanMultiTerm search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanMultiTermTest extends AbstractComplexSpanQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanMultiTermQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanMultiTermQuery(
            $this->getSubQueryMock('matchSpanQuery')
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_multi']);

        $this->assertEquals('matchSpanQuery', $query['span_multi']['match']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_multi']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanMultiTermQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanMultiTermQuery(
            $this->getSubQueryMock('matchSpanQuery'),
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_multi']);

        $this->assertEquals('matchSpanQuery', $query['span_multi']['match']);
        $this->assertEquals(17, $query['span_multi']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanMultiTermQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_multi', $query);

        $this->assertArrayHasKey('match', $query['span_multi']);
        $this->assertArrayHasKey('boost', $query['span_multi']);
    }
}
