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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\PipelineBuilder\MaxBucket as MaxBucketBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Pipeline\MaxBucket;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Search adapter bucket selector pipeline builder test cases.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class MaxBucketTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a bucket selector aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new MaxBucket('aggregationName', 'bucket.path');

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('max_bucket', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['max_bucket']['buckets_path']);
        $this->assertEquals('', $aggregation['max_bucket']['format']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_SKIP, $aggregation['max_bucket']['gap_policy']);
    }

    /**
     * Build a bucket selector aggregation from a bucket.
     */
    public function testComplexAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new MaxBucket('aggregationName', 'bucket.path', PipelineInterface::GAP_POLICY_INSERT_ZEROS, 'testFormat');

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('max_bucket', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['max_bucket']['buckets_path']);
        $this->assertEquals('testFormat', $aggregation['max_bucket']['format']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_INSERT_ZEROS, $aggregation['max_bucket']['gap_policy']);
    }

    /**
     * Test an exception is thrown when using the max bucket pipeline with another pipeline type.
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
     * @return MaxBucketBuilder
     */
    private function getPipelineBuilder()
    {
        return new MaxBucketBuilder();
    }
}
