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

use Smile\ElasticsuiteCore\Search\Request\Query\Nested as NestedQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Nested as NestedQueryBuilder;

/**
 * Nested search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class NestedTest extends AbstractComplexQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousNestedQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $nestedQuery = new NestedQuery('nestedPath', $this->getSubQueryMock('subquery'));
        $query = $builder->buildQuery($nestedQuery);

        $this->assertArrayHasKey('nested', $query);
        $this->assertEquals('nestedPath', $query['nested']['path']);
        $this->assertEquals('subquery', $query['nested']['query']);
        $this->assertEquals(NestedQuery::SCORE_MODE_NONE, $query['nested']['score_mode']);
        $this->assertEquals(NestedQuery::DEFAULT_BOOST_VALUE, $query['nested']['boost']);

        $this->assertArrayNotHasKey('_name', $query['nested']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedNestedQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $nestedQuery = new NestedQuery('nestedPath', $this->getSubQueryMock('subquery'), NestedQuery::SCORE_MODE_NONE, 'queryName');
        $query = $builder->buildQuery($nestedQuery);

        $this->assertArrayHasKey('_name', $query['nested']);
        $this->assertEquals('queryName', $query['nested']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new NestedQueryBuilder($this->getParentQueryBuilder());
    }
}
