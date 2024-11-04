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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Prefix as PrefixQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Prefix as PrefixQuery;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Prefix search request query building test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class PrefixTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     */
    public function testAnonymousPrefixQueryAssembler(): void
    {
        $builder = $this->getQueryBuilder();

        $prefixQuery = new PrefixQuery('search text', 'searchField');
        $query = $builder->buildQuery($prefixQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayNotHasKey('_name', $query['prefix']);

        $this->assertArrayHasKey('searchField', $query['prefix']);
        $this->assertEquals('search text', $query['prefix']['searchField']['value']);
        $this->assertEquals(QueryInterface::DEFAULT_BOOST_VALUE, $query['prefix']['searchField']['boost']);
    }

    /**
     * Test the query builder with mandatory + name params.
     */
    public function testNamedPrefixQueryBuilder(): void
    {
        $builder = $this->getQueryBuilder();

        $prefixQuery = new PrefixQuery(
            'search text',
            'searchField',
            'queryName',
            17.5
        );
        $query = $builder->buildQuery($prefixQuery);

        $this->checkDefaultStructure($query);
        $this->assertArrayHasKey('_name', $query['prefix']);
        $this->assertEquals('queryName', $query['prefix']['_name']);

        $this->assertArrayHasKey('searchField', $query['prefix']);
        $this->assertEquals('search text', $query['prefix']['searchField']['value']);
        $this->assertEquals(17.5, $query['prefix']['searchField']['boost']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new PrefixQueryBuilder();
    }

    /**
     * Check the minimum structure of the built query.
     *
     * @param array $query Built query
     */
    private function checkDefaultStructure(array $query): void
    {
        $this->assertArrayHasKey('prefix', $query);
        $this->assertIsArray($query['prefix']);

        $innerStructure = current($query['prefix']);
        $this->assertArrayHasKey('value', $innerStructure);
        $this->assertArrayHasKey('boost', $innerStructure);
    }
}
