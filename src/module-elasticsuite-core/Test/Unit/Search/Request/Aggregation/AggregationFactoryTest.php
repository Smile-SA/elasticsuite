<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\AggregationInterface;

/**
 * Search request aggregation builder test case.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the aggregation creation using the factory.
     *
     * @return void
     */
    public function testAggregationCreate()
    {
        $aggregation = $this->getAggregationFactory()->create('aggregationType', []);
        $this->assertInstanceOf(AggregationInterface::class, $aggregation);
    }

    /**
     * Test submitting an invalid aggregation type throws an exception.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage No factory found for aggregation of type invalidAggregationType
     *
     * @return void
     */
    public function testInvalidAggregationCreate()
    {
        $this->getAggregationFactory()->create('invalidAggregationType', []);
    }

    /**
     * Prepared a mocked aggregation factory.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    private function getAggregationFactory()
    {
        $aggregationMock = $this->getMockBuilder(AggregationInterface::class)->getMock();

        $factoryName      = sprintf("%s%s", AggregationInterface::class, 'Factory');
        $aggregationFactoryMock = $this->getMockBuilder($factoryName)
            ->setMethods(['create'])
            ->getMock();

        $aggregationFactoryMock->method('create')
            ->will($this->returnValue($aggregationMock));

        $factories = ['aggregationType' => $aggregationFactoryMock];

        return new AggregationFactory($factories);
    }
}
