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

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\MoreLikeThis as MoreLikeThisQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\MoreLikeThis as MoreLikeThisQueryBuilder;

/**
 * MoreLikeThis search request query building test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class MoreLikeThisTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousMoreLikeThisQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MoreLikeThisQuery(['searchField'], 'search like text');
        $query = $builder->buildQuery($matchQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['more_like_this']);

        $this->assertEquals(['searchField'], $query['more_like_this']['fields']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_MINIMUM_SHOULD_MATCH, $query['more_like_this']['minimum_should_match']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_BOOST_VALUE, $query['more_like_this']['boost']);
        $this->assertEquals('search like text', $query['more_like_this']['like']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_BOOST_TERMS, $query['more_like_this']['boost_terms']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_MIN_TERM_FREQ, $query['more_like_this']['min_term_freq']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_MIN_DOC_FREQ, $query['more_like_this']['min_doc_freq']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_MAX_DOC_FREQ, $query['more_like_this']['max_doc_freq']);
        $this->assertEquals(MoreLikeThisQuery::DEFAULT_MAX_QUERY_TERMS, $query['more_like_this']['max_query_terms']);
        $this->assertFalse($query['more_like_this']['include']);
    }

    /**
     * Test the query builder with mandatory + name params.
     */
    public function testNamedMoreLikeThisQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $moreLikeThisQuery = new MoreLikeThisQuery(
            ['searchField1', 'searchField2'],
            [['_id' => 1], ['_id' => 2]],
            '3<75%',
            10,
            5,
            2,
            75,
            25,
            1,
            20,
            true,
            'queryName',
            15
        );
        $query = $builder->buildQuery($moreLikeThisQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayHasKey('_name', $query['more_like_this']);

        $this->assertEquals(['searchField1', 'searchField2'], $query['more_like_this']['fields']);
        $this->assertEquals('3<75%', $query['more_like_this']['minimum_should_match']);
        $this->assertEquals(15, $query['more_like_this']['boost']);
        $this->assertEquals([['_id' => 1], ['_id' => 2]], $query['more_like_this']['like']);
        $this->assertEquals(10, $query['more_like_this']['boost_terms']);
        $this->assertEquals(5, $query['more_like_this']['min_term_freq']);
        $this->assertEquals(2, $query['more_like_this']['min_doc_freq']);
        $this->assertEquals(75, $query['more_like_this']['max_doc_freq']);
        $this->assertEquals(25, $query['more_like_this']['max_query_terms']);
        $this->assertEquals(1, $query['more_like_this']['min_word_length']);
        $this->assertEquals(20, $query['more_like_this']['max_word_length']);
        $this->assertTrue($query['more_like_this']['include']);
        $this->assertEquals('queryName', $query['more_like_this']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new MoreLikeThisQueryBuilder();
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('more_like_this', $query);

        $this->assertArrayHasKey('fields', $query['more_like_this']);
        $this->assertArrayHasKey('minimum_should_match', $query['more_like_this']);
        $this->assertArrayHasKey('boost', $query['more_like_this']);
        $this->assertArrayHasKey('like', $query['more_like_this']);
        $this->assertArrayHasKey('boost_terms', $query['more_like_this']);
        $this->assertArrayHasKey('min_term_freq', $query['more_like_this']);
        $this->assertArrayHasKey('min_doc_freq', $query['more_like_this']);
        $this->assertArrayHasKey('max_doc_freq', $query['more_like_this']);
        $this->assertArrayHasKey('max_query_terms', $query['more_like_this']);
        $this->assertArrayHasKey('min_word_length', $query['more_like_this']);
        $this->assertArrayHasKey('max_word_length', $query['more_like_this']);
        $this->assertArrayHasKey('include', $query['more_like_this']);
    }
}
