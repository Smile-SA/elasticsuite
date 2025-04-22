<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\Query\Vector\Opensearch\Neural as NeuralQuery;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder\Opensearch\Neural as NeuralQueryBuilder;

/**
 * Neural search request query test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 */
class NeuralTest extends AbstractSimpleQueryBuilder
{
    /**
     * @dataProvider neuralQueryAssemblerDataProvider
     *
     * @param array $queryParameters Query Parameters
     * @param array $expectedQuery   The assembled query
     *
     * @return void
     */
    public function testNeuralQueryAssembler(
        array $queryParameters,
        array $expectedQuery,
    ): void {
        $this->assertEquals(
            $expectedQuery,
            $this->getQueryBuilder()->buildQuery(new NeuralQuery(...$queryParameters))
        );
    }

    /**
     * Data provider
     *
     * @return iterable
     */
    public function neuralQueryAssemblerDataProvider(): iterable
    {
        yield [
            ['search text', 100],
            [
                'neural' => [
                    'embedding' => [
                        'query_text' => 'search text',
                        'k' => 100,
                        'boost' => 1,
                    ],
                ],
            ],
        ];
        yield [
            ['search text 2', 200, 'customField', 'Query Name', 2.5, 'customModel'],
            [
                'neural' => [
                    'customField' => [
                        'query_text' => 'search text 2',
                        'k' => 200,
                        'boost' => 2.5,
                        'model_id' => 'customModel',
                        '_name' => 'Query Name',
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryBuilder()
    {
        return new NeuralQueryBuilder();
    }
}
