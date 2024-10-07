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

use Smile\ElasticsuiteCore\Search\Request\Query\Range as RangeQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Range as RangeQueryBuilder;

/**
 * Range search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RangeTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousRangeQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $rangeQuery = new RangeQuery('field', ['bounds']);
        $query = $builder->buildQuery($rangeQuery);

        $this->assertArrayHasKey('range', $query);
        $this->assertArrayHasKey('field', $query['range']);
        $this->assertEquals(['bounds'] + ['boost' => RangeQuery::DEFAULT_BOOST_VALUE], $query['range']['field']);
        $this->assertEquals(RangeQuery::DEFAULT_BOOST_VALUE, $query['range']['field']['boost']);

        $this->assertArrayNotHasKey('_name', $query['range']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new RangeQueryBuilder();
    }
}
