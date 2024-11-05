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

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\SignificantTerm as SignificantTermBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\SignificantTerm;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter significant term aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class SignificantTermTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a significant term aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = new SignificantTermBuilder();
        $bucket = new SignificantTerm('aggregationName', 'fieldName');

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('significant_terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['significant_terms']['field']);
        $this->assertEquals(BucketInterface::MAX_BUCKET_SIZE, $aggregation['significant_terms']['size']);
        $this->assertEquals(5, $aggregation['significant_terms']['min_doc_count']);
        $this->assertArrayHasKey('gnd', $aggregation['significant_terms']);
    }

    /**
     * Build a significant term aggregation from a bucket.
     */
    public function testComplexAggregationBuild(): void
    {
        $aggBuilder = new SignificantTermBuilder();
        $bucket = new SignificantTerm(
            'aggregationName',
            'fieldName',
            [],
            [],
            [],
            null,
            null,
            null,
            12,
            10,
            SignificantTerm::ALGORITHM_PERCENTAGE,
        );

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('significant_terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['significant_terms']['field']);
        $this->assertEquals(12, $aggregation['significant_terms']['size']);
        $this->assertEquals(10, $aggregation['significant_terms']['min_doc_count']);
        $this->assertArrayNotHasKey('gnd', $aggregation['significant_terms']);
        $this->assertArrayHasKey('percentage', $aggregation['significant_terms']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new SignificantTermBuilder();
        $this->expectExceptionMessage('Query builder : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->buildBucket($termsBucket);
    }
}
