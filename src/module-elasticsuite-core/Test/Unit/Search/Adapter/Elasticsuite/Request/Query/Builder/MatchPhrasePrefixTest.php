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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\MatchPhrasePrefix as MatchPhrasePrefixQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\MatchPhrasePrefix as MatchPhrasePrefixQuery;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * MatchPhrasePrefix search request query building test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class MatchPhrasePrefixTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousMatchPhrasePrefixQueryAssembler(): void
    {
        $assembler = $this->getQueryBuilder();

        $matchPhrasePrefixQuery = new MatchPhrasePrefixQuery('search text', 'searchField');
        $query = $assembler->buildQuery($matchPhrasePrefixQuery);

        $this->checkDefaultStructure($query);

        $this->assertEquals('search text', $query['match_phrase_prefix']['searchField']['query']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['match_phrase_prefix']['searchField']['boost']);
        $this->assertEquals(10, $query['match_phrase_prefix']['searchField']['max_expansions']);

        $this->assertArrayNotHasKey('_name', $query['match_phrase_prefix']);
    }

    /**
     * Test the assembler with mandatory + name params.
     */
    public function testNamedMatchPhrasePrefixQueryAssembler(): void
    {
        $assembler = $this->getQueryBuilder();

        $matchPhrasePrefixQuery = new MatchPhrasePrefixQuery(
            'search text',
            'searchField',
            10,
            'queryName',
            QueryInterface::DEFAULT_BOOST_VALUE
        );
        $query = $assembler->buildQuery($matchPhrasePrefixQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayHasKey('_name', $query['match_phrase_prefix']);

        $this->assertEquals('search text', $query['match_phrase_prefix']['searchField']['query']);
        $this->assertEquals(MatchPhrasePrefixQuery::DEFAULT_BOOST_VALUE, $query['match_phrase_prefix']['searchField']['boost']);
        $this->assertEquals(10, $query['match_phrase_prefix']['searchField']['max_expansions']);
        $this->assertEquals('queryName', $query['match_phrase_prefix']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new MatchPhrasePrefixQueryBuilder();
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('match_phrase_prefix', $query);
        $this->assertArrayHasKey('searchField', $query['match_phrase_prefix']);
        $this->assertArrayHasKey('query', $query['match_phrase_prefix']['searchField']);
        $this->assertArrayHasKey('boost', $query['match_phrase_prefix']['searchField']);
        $this->assertArrayHasKey('max_expansions', $query['match_phrase_prefix']['searchField']);
    }
}
