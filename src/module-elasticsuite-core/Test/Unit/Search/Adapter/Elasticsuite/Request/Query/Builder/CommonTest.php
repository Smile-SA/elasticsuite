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

use Smile\ElasticsuiteCore\Search\Request\Query\Common as CommonQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Common as CommonQueryBuilder;

/**
 * Common search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CommonTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousCommonQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $commonQuery = new CommonQuery('search text', 'searchField');
        $query = $builder->buildQuery($commonQuery);

        $this->assertArrayHasKey('common', $query);
        $this->assertArrayHasKey('searchField', $query['common']);
        $this->assertEquals('search text', $query['common']['searchField']['query']);
        $this->assertEquals(CommonQuery::DEFAULT_MINIMUM_SHOULD_MATCH, $query['common']['searchField']['minimum_should_match']);
        $this->assertEquals(CommonQuery::DEFAULT_CUTOFF_FREQUENCY, $query['common']['searchField']['cutoff_frequency']);
        $this->assertEquals(CommonQuery::DEFAULT_MINIMUM_SHOULD_MATCH, $query['common']['searchField']['minimum_should_match']);

        $this->assertArrayNotHasKey('_name', $query['common']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedCommonQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $commonQuery = new CommonQuery(
            'search text',
            'searchField',
            CommonQuery::DEFAULT_CUTOFF_FREQUENCY,
            CommonQuery::DEFAULT_BOOST_VALUE,
            'queryName'
        );

        $query = $builder->buildQuery($commonQuery);

        $this->assertArrayHasKey('_name', $query['common']);
        $this->assertEquals('queryName', $query['common']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new CommonQueryBuilder();
    }
}
