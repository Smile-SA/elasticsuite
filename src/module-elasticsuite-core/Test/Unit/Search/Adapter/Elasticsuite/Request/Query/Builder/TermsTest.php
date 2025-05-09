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

use Smile\ElasticsuiteCore\Search\Request\Query\Terms as TermsQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Terms as TermsQueryBuilder;

/**
 * Terms search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TermsTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousTermsQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $termsQuery = new TermsQuery('value', 'field');
        $query = $builder->buildQuery($termsQuery);

        $this->assertArrayHasKey('terms', $query);
        $this->assertArrayHasKey('field', $query['terms']);
        $this->assertEquals(['value'], $query['terms']['field']);
        $this->assertEquals(TermsQuery::DEFAULT_BOOST_VALUE, $query['terms']['boost']);
        $this->assertArrayNotHasKey('_name', $query['terms']);

        $termsQuery = new TermsQuery(['value'], 'field');
        $query = $builder->buildQuery($termsQuery);
        $this->assertEquals(['value'], $query['terms']['field']);

        $termsQuery = new TermsQuery(['value1', 'value2'], 'field');
        $query = $builder->buildQuery($termsQuery);
        $this->assertEquals(['value1', 'value2'], $query['terms']['field']);

        $termsQuery = new TermsQuery('value1,value2', 'field');
        $query = $builder->buildQuery($termsQuery);
        $this->assertEquals(['value1', 'value2'], $query['terms']['field']);

        $termsQuery = new TermsQuery(true, 'field');
        $query = $builder->buildQuery($termsQuery);
        $this->assertEquals([true], $query['terms']['field']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedTermsQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $termsQuery = new TermsQuery('value', 'field', 'queryName');
        $query = $builder->buildQuery($termsQuery);

        $this->assertArrayHasKey('_name', $query['terms']);
        $this->assertEquals('queryName', $query['terms']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new TermsQueryBuilder();
    }
}
