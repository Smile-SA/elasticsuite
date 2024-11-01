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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Span\SpanTerm as SpanTermQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Span\SpanTerm as SpanTermQuery;
use Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder\AbstractSimpleQueryBuilderTest;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * SpanTerm search request query building test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SpanTermTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousSpanTermQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanTermQuery(
            'search text',
            'searchField'
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['span_term']);

        $this->assertArrayHasKey('searchField', $query['span_term']);
        $this->assertEquals('search text', $query['span_term']['searchField']['value']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['span_term']['searchField']['boost']);
    }

    /**
     * Test the builder with mandatory + other params.
     */
    public function testNamedTermQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $spanQuery = new SpanTermQuery(
            'search text',
            'searchField',
            'queryName',
            17
        );
        $query = $builder->buildQuery($spanQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayHasKey('_name', $query['span_term']);

        $this->assertArrayHasKey('searchField', $query['span_term']);
        $this->assertEquals('search text', $query['span_term']['searchField']['value']);
        $this->assertEquals(17, $query['span_term']['searchField']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new SpanTermQueryBuilder();
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('span_term', $query);
        $this->assertIsArray($query['span_term']);

        $innerStructure = current($query['span_term']);
        $this->assertIsArray($innerStructure);
        $this->assertArrayHasKey('value', $innerStructure);
        $this->assertArrayHasKey('boost', $innerStructure);
    }
}
