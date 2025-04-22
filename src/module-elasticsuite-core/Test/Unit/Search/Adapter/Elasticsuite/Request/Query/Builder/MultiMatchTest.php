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

use Smile\ElasticsuiteCore\Search\Request\Query\MultiMatch as MultiMatchQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\MultiMatch as MultiMatchQueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;

/**
 * MultiMatch search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MultiMatchTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousMultiMatchQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MultiMatchQuery('search text', ['searchField' => 1]);
        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('multi_match', $query);
        $this->assertEquals('search text', $query['multi_match']['query']);
        $this->assertContains('searchField^1', $query['multi_match']['fields']);
        $this->assertCount(1, $query['multi_match']['fields']);
        $this->assertEquals(MultiMatchQuery::DEFAULT_MINIMUM_SHOULD_MATCH, $query['multi_match']['minimum_should_match']);
        $this->assertEquals(MultiMatchQuery::DEFAULT_TIE_BREAKER, $query['multi_match']['tie_breaker']);
        $this->assertEquals(MultiMatchQuery::DEFAULT_MATCH_TYPE, $query['multi_match']['type']);
        $this->assertEquals(MultiMatchQuery::DEFAULT_BOOST_VALUE, $query['multi_match']['boost']);
        $this->assertArrayNotHasKey('_name', $query['multi_match']);
        $this->assertArrayNotHasKey('cutoff_frequency', $query['multi_match']);
        $this->assertArrayNotHasKey('fuzziness', $query['multi_match']);
        $this->assertArrayNotHasKey('prefix_length', $query['multi_match']);
        $this->assertArrayNotHasKey('max_expansions', $query['multi_match']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedMultiMatchQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MultiMatchQuery(
            'search text',
            ['searchField' => 1],
            MultiMatchQuery::DEFAULT_MINIMUM_SHOULD_MATCH,
            MultiMatchQuery::DEFAULT_TIE_BREAKER,
            'queryName'
        );

        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('_name', $query['multi_match']);
        $this->assertEquals('queryName', $query['multi_match']['_name']);
    }

    /**
     * Test the builder with mandatory + cutoff_frequency params.
     *
     * @return void
     */
    public function testCutoffFrequencyMultiMatchQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $matchQuery = new MultiMatchQuery(
            'search text',
            ['searchField' => 1],
            MultiMatchQuery::DEFAULT_MINIMUM_SHOULD_MATCH,
            MultiMatchQuery::DEFAULT_TIE_BREAKER,
            null,
            MultiMatchQuery::DEFAULT_BOOST_VALUE,
            null,
            0.1
        );

        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('cutoff_frequency', $query['multi_match']);
        $this->assertEquals(0.1, $query['multi_match']['cutoff_frequency']);
    }

    /**
     * Test the builder with mandatory + fuzziness params.
     *
     * @return void
     */
    public function testFuzzyMultiMatchQueryBuilder()
    {
        $fuzzyConfiguration = $this->getMockBuilder(FuzzinessConfigurationInterface::class)->getMock();
        $fuzzyConfiguration->method('getValue')->will($this->returnValue('AUTO'));
        $fuzzyConfiguration->method('getPrefixLength')->will($this->returnValue(1));
        $fuzzyConfiguration->method('getMaxExpansion')->will($this->returnValue(10));

        $builder = $this->getQueryBuilder();

        $matchQuery = new MultiMatchQuery(
            'search text',
            ['searchField' => 1],
            MultiMatchQuery::DEFAULT_MINIMUM_SHOULD_MATCH,
            MultiMatchQuery::DEFAULT_TIE_BREAKER,
            null,
            MultiMatchQuery::DEFAULT_BOOST_VALUE,
            $fuzzyConfiguration
        );

        $query = $builder->buildQuery($matchQuery);

        $this->assertArrayHasKey('fuzziness', $query['multi_match']);
        $this->assertEquals('AUTO', $query['multi_match']['fuzziness']);

        $this->assertArrayHasKey('prefix_length', $query['multi_match']);
        $this->assertEquals(1, $query['multi_match']['prefix_length']);

        $this->assertArrayHasKey('max_expansions', $query['multi_match']);
        $this->assertEquals(10, $query['multi_match']['max_expansions']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new MultiMatchQueryBuilder();
    }
}
