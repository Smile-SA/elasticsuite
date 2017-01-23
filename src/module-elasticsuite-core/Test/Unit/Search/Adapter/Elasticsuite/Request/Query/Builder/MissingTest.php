<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\Missing as MissingQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Missing as MissingQueryBuilder;

/**
 * Missing search request query test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MissingTest extends AbstractSimpleQueryBuilderTest
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousMissingQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $missingQuery = new MissingQuery('field');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('missing', $query);
        $this->assertArrayHasKey('field', $query['missing']);
        $this->assertArrayNotHasKey('_name', $query['missing']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedMissingQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $missingQuery = new MissingQuery('field', 'queryName');
        $query = $builder->buildQuery($missingQuery);

        $this->assertArrayHasKey('_name', $query['missing']);
        $this->assertEquals('queryName', $query['missing']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new MissingQueryBuilder();
    }
}
