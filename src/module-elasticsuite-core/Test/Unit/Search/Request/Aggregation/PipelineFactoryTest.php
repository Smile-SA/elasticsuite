<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Search request pipeline aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class PipelineFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the pipeline aggregation creation using the factory.
     *
     * @return void
     */
    public function testPipelineCreate()
    {
        $pipeline = $this->getPipelineFactory()->create('pipelineType', []);
        $this->assertInstanceOf(PipelineInterface::class, $pipeline);
    }

    /**
     * Test trying to create an invalid pipeline aggregation type throws an exception.
     *
     * @return void
     */
    public function testInvalidPipelineCreate()
    {
        $this->expectExceptionMessage("No factory found for pipeline aggregation of type invalidPipelineType");
        $this->expectException(\LogicException::class);
        $this->getPipelineFactory()->create('invalidPipelineType', []);
    }

    /**
     * Prepared a mocked pipeline aggregation factory.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory
     */
    private function getPipelineFactory()
    {
        $pipelineMock = $this->getMockBuilder(PipelineInterface::class)->getMock();

        $factoryName = sprintf("%s%s", PipelineInterface::class, 'Factory');
        $pipelineFactoryMock = $this->getMockBuilder($factoryName)
            ->onlyMethods(['create'])
            ->getMock();

        $pipelineFactoryMock->method('create')
            ->will($this->returnValue($pipelineMock));

        $factories = ['pipelineType' => $pipelineFactoryMock];

        return new PipelineFactory($factories);
    }
}
