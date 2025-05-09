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

use Smile\ElasticsuiteCore\Search\Request\Query\Term as TermQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Term as TermQueryBuilder;

/**
 * Term search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TermTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousTermQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $termQuery = new TermQuery('value', 'field');
        $query = $builder->buildQuery($termQuery);

        $this->assertArrayHasKey('term', $query);
        $this->assertArrayHasKey('field', $query['term']);
        $this->assertEquals('value', $query['term']['field']['value']);
        $this->assertEquals(TermQuery::DEFAULT_BOOST_VALUE, $query['term']['field']['boost']);

        $this->assertArrayNotHasKey('_name', $query['term']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedTermQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $termQuery = new TermQuery('value', 'field', 'queryName');
        $query = $builder->buildQuery($termQuery);

        $this->assertArrayHasKey('_name', $query['term']);
        $this->assertEquals('queryName', $query['term']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new TermQueryBuilder();
    }
}
