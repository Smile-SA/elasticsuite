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

use Smile\ElasticsuiteCore\Search\Request\Query\Not as NotQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Not as NotQueryBuilder;

/**
 * Not search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class NotTest extends AbstractComplexQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousNotQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $notQuery = new NotQuery($this->getSubQueryMock('subquery'));
        $query = $builder->buildQuery($notQuery);

        $this->assertArrayHasKey('bool', $query);
        $this->assertArrayHasKey('must_not', $query['bool']);
        $this->assertEquals('subquery', current($query['bool']['must_not']));
        $this->assertEquals(NotQuery::DEFAULT_BOOST_VALUE, $query['bool']['boost']);

        $this->assertArrayNotHasKey('_name', $query['bool']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedNotQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $notQuery = new NotQuery($this->getSubQueryMock('subquery'), 'queryName');
        $query = $builder->buildQuery($notQuery);

        $this->assertArrayHasKey('_name', $query['bool']);
        $this->assertEquals('queryName', $query['bool']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new NotQueryBuilder($this->getParentQueryBuilder());
    }
}
