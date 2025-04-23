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

use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore as FunctionScoreQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\FunctionScore as FunctionScoreQueryBuilder;

/**
 * FunctionScore search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FunctionScoreTest extends AbstractComplexQueryBuilder
{
    /**
     * Test the builder with mandatory params only.
     *
     * @return void
     */
    public function testAnonymousFunctionScoreQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $baseQuery = $this->getSubQueryMock('baseQuery');
        $functions = [
            'filterFunction' => [
                'filter'       => $this->getSubQueryMock('filterFunctionQuery'),
                'functionName' => 'filteredFunction',
            ],
            'unfilteredFunction' => [
                'functionName' => 'unfilteredFunction',
            ],
        ];

        $functionScoreQuery = new FunctionScoreQuery($baseQuery, $functions);
        $query = $builder->buildQuery($functionScoreQuery);

        $this->assertArrayHasKey('function_score', $query);

        $this->assertArrayHasKey('query', $query['function_score']);
        $this->assertEquals('baseQuery', $query['function_score']['query']);

        $this->assertArrayHasKey('functions', $query['function_score']);
        $this->assertCount(2, $query['function_score']['functions']);

        $this->assertEquals('filterFunctionQuery', $query['function_score']['functions'][0]['filter']);
        $this->assertEquals('filteredFunction', $query['function_score']['functions'][0]['functionName']);
        $this->assertEquals('unfilteredFunction', $query['function_score']['functions'][1]['functionName']);

        $this->assertEquals(FunctionScoreQuery::SCORE_MODE_SUM, $query['function_score']['score_mode']);
        $this->assertEquals(FunctionScoreQuery::BOOST_MODE_SUM, $query['function_score']['boost_mode']);

        $this->assertArrayNotHasKey('_name', $query['function_score']);
    }

    /**
     * Test the builder with mandatory + name params.
     *
     * @return void
     */
    public function testNamedFunctionScoreQueryBuilder()
    {
        $builder = $this->getQueryBuilder();

        $functionScoreQuery = new FunctionScoreQuery($this->getSubQueryMock('baseQuery'), [], 'queryName');
        $query = $builder->buildQuery($functionScoreQuery);

        $this->assertArrayHasKey('_name', $query['function_score']);
        $this->assertEquals('queryName', $query['function_score']['_name']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new FunctionScoreQueryBuilder($this->getParentQueryBuilder());
    }
}
