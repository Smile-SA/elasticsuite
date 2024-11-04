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

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\PipelineBuilder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\PipelineBuilder\MovingFunction as MovingFunctionBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Pipeline\MovingFunction;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Search adapter bucket selector pipeline builder test cases.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class MovingFunctionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a moving function aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new MovingFunction('aggregationName', 'bucket.path', 'testScript');

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('moving_fn', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['moving_fn']['buckets_path']);
        $this->assertEquals('testScript', $aggregation['moving_fn']['script']);
        $this->assertEquals(10, $aggregation['moving_fn']['window']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_SKIP, $aggregation['moving_fn']['gap_policy']);
    }

    /**
     * Build a moving function aggregation from a bucket.
     */
    public function testComplexAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new MovingFunction('aggregationName', 'bucket.path', 'testScript', 20, PipelineInterface::GAP_POLICY_INSERT_ZEROS);

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('moving_fn', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['moving_fn']['buckets_path']);
        $this->assertEquals('testScript', $aggregation['moving_fn']['script']);
        $this->assertEquals(20, $aggregation['moving_fn']['window']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_INSERT_ZEROS, $aggregation['moving_fn']['gap_policy']);
    }

    /**
     * Test an exception is thrown when using the bucket selector pipeline with another pipeline type.
     */
    public function testInvalidPipelineBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $this->expectExceptionMessage('Query builder : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $pipeline = $this->getMockBuilder(PipelineInterface::class)->getMock();
        $pipeline->method('getType')->willReturn('invalidType');

        $pipelineBuilder->buildPipeline($pipeline);
    }

    /**
     * Aggregation builder used in tests.
     *
     * @return MovingFunctionBuilder
     */
    private function getPipelineBuilder()
    {
        return new MovingFunctionBuilder();
    }
}
