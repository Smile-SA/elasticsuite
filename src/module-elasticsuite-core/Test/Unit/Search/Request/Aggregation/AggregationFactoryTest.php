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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\Term;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search request bucket aggregation factory test case.
 *
 * @category Smile
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
        $this->assertInstanceOf(BucketInterface::class, $aggregation);
    }

    /**
     * Test submitting an invalid aggregation type throws an exception.
     *
     * @return void
     */
    public function testInvalidAggregationCreate()
    {
        $this->expectExceptionMessage("No factory found for aggregation of type invalidAggregationType");
        $this->expectException(\LogicException::class);
        $this->getAggregationFactory()->create('invalidAggregationType', []);
    }

    /**
     * Prepared a mocked aggregation factory.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    private function getAggregationFactory()
    {
        $aggregationMock = $this->getMockBuilder(Term::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factoryName      = sprintf("%s%s", Term::class, 'Factory');
        $aggregationFactoryMock = $this->getMockBuilder($factoryName)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $aggregationFactoryMock->method('create')
            ->will($this->returnValue($aggregationMock));

        $factories = ['aggregationType' => $aggregationFactoryMock];

        return new AggregationFactory($factories);
    }
}
