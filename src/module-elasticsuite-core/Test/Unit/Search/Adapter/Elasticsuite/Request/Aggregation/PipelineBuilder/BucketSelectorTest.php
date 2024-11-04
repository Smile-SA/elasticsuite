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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\PipelineBuilder\BucketSelector as BucketSelectorBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Pipeline\BucketSelector;
use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Search adapter bucket selector pipeline builder test cases.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class BucketSelectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a bucket selector aggregation from a pipeline.
     */
    public function testBasicAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new BucketSelector('aggregationName', 'bucket.path', 'testScript');

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('bucket_selector', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['bucket_selector']['buckets_path']);
        $this->assertEquals('testScript', $aggregation['bucket_selector']['script']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_SKIP, $aggregation['bucket_selector']['gap_policy']);
    }

    /**
     * Build a bucket selector aggregation from a pipeline.
     */
    public function testComplexAggregationBuild(): void
    {
        $pipelineBuilder = $this->getPipelineBuilder();
        $pipeline = new BucketSelector('aggregationName', 'bucket.path', 'testScript', PipelineInterface::GAP_POLICY_INSERT_ZEROS);

        $aggregation = $pipelineBuilder->buildPipeline($pipeline);

        $this->assertArrayHasKey('bucket_selector', $aggregation);
        $this->assertEquals('bucket.path', $aggregation['bucket_selector']['buckets_path']);
        $this->assertEquals('testScript', $aggregation['bucket_selector']['script']);
        $this->assertEquals(PipelineInterface::GAP_POLICY_INSERT_ZEROS, $aggregation['bucket_selector']['gap_policy']);
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
     * @return BucketSelectorBuilder
     */
    private function getPipelineBuilder()
    {
        return new BucketSelectorBuilder();
    }
}
