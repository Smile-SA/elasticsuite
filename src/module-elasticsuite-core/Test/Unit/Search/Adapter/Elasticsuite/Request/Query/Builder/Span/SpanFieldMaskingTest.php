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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanFieldMasking as SpanFieldMaskingQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanFieldMasking as SpanFieldMaskingQuery;

/**
 * SpanFieldMasking search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanFieldMaskingTest extends AbstractComplexSpanQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanFieldMaskingQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanFieldMaskingQuery(
            $this->getSubQueryMock('internalSpanQuery'),
            'searchField'
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_field_masking']);

        $this->assertEquals('internalSpanQuery', $query['span_field_masking']['query']);
        $this->assertEquals('searchField', $query['span_field_masking']['field']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_field_masking']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testExtendedSpanFieldMaskingQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanFieldMaskingQuery(
            $this->getSubQueryMock('internalSpanQuery'),
            'searchField',
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        // Name not supported yet.
        $this->assertArrayNotHasKey('_name', $query['span_field_masking']);

        $this->assertEquals('internalSpanQuery', $query['span_field_masking']['query']);
        $this->assertEquals('searchField', $query['span_field_masking']['field']);
        $this->assertEquals(17, $query['span_field_masking']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanFieldMaskingQueryBuilder($this->getParentQueryBuilder());
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_field_masking', $query);

        $this->assertArrayHasKey('boost', $query['span_field_masking']);
        $this->assertArrayHasKey('query', $query['span_field_masking']);
        $this->assertArrayHasKey('field', $query['span_field_masking']);
    }
}
