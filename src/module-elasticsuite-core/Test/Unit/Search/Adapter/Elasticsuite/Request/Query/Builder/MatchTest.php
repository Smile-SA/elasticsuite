<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\MatchQuery as MatchQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\MatchQuery as MatchQueryBuilder;

/**
 * Match search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MatchTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousMatchQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MatchQuery('search text', 'searchField');
        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('match', $query);
        $this->assertArrayHasKey('searchField', $query['match']);
        $this->assertEquals('search text', $query['match']['searchField']['query']);
        $this->assertEquals(MatchQuery::DEFAULT_MINIMUM_SHOULD_MATCH, $query['match']['searchField']['minimum_should_match']);
        $this->assertEquals(MatchQuery::DEFAULT_BOOST_VALUE, $query['match']['searchField']['boost']);

        $this->assertArrayNotHasKey('_name', $query['match']['searchField']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedMatchQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MatchQuery('search text', 'searchField', MatchQuery::DEFAULT_BOOST_VALUE, 'queryName');
        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('_name', $query['match']['searchField']);
        $this->assertEquals('queryName', $query['match']['searchField']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new MatchQueryBuilder();
    }
}
