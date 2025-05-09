<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\Regexp as RegexpQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Regexp as RegexpQueryBuilder;

/**
 * Regexp search query test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class RegexpTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousRegexpQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $regexpQuery = new RegexpQuery('value', 'field');
        $query = $builder->buildQuery($regexpQuery);

        $this->assertArrayHasKey('regexp', $query);
        $this->assertArrayHasKey('field', $query['regexp']);
        $this->assertArrayHasKey('value', $query['regexp']['field']);
        $this->assertEquals('value', $query['regexp']['field']['value']);

        $this->assertArrayHasKey('boost', $query['regexp']['field']);
        $this->assertEquals(RegexpQuery::DEFAULT_BOOST_VALUE, $query['regexp']['field']['boost']);

        $this->assertArrayHasKey('flags', $query['regexp']['field']);
        $this->assertEquals($builder::DEFAULT_FLAGS, $query['regexp']['field']['flags']);

        $this->assertArrayNotHasKey('_name', $query['regexp']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedRegexpQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $regexpQuery = new RegexpQuery('value', 'field', 'queryName');
        $query = $builder->buildQuery($regexpQuery);

        $this->assertArrayHasKey('regexp', $query);
        $this->assertArrayHasKey('_name', $query['regexp']);
        $this->assertEquals('queryName', $query['regexp']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new RegexpQueryBuilder();
    }
}
