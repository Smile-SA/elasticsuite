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

use Smile\ElasticsuiteCore\Search\Request\Query\Exists as ExistsQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Exists as ExistsQueryBuilder;

/**
 * Exists search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ExistsTest extends AbstractSimpleQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousMissingQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $missingQuery = new ExistsQuery('field');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('exists', $query);
        $this->assertArrayHasKey('field', $query['exists']);
        $this->assertArrayNotHasKey('_name', $query['exists']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedMissingQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $missingQuery = new ExistsQuery('field', 'queryName');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('_name', $query['exists']);
        $this->assertEquals('queryName', $query['exists']['_name']);
    }

    /**
     * Test the builder with a query named or renamed after creation.
     *
     * @return void
     */
    public function testLaterNamedMissingQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $missingQuery = new ExistsQuery('field');
        $missingQuery->setName('queryName');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('_name', $query['exists']);
        $this->assertEquals('queryName', $query['exists']['_name']);

        $missingQuery = new ExistsQuery('field', 'originalQueryName');
        $missingQuery->setName('queryName');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('_name', $query['exists']);
        $this->assertEquals('queryName', $query['exists']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new ExistsQueryBuilder();
    }
}
