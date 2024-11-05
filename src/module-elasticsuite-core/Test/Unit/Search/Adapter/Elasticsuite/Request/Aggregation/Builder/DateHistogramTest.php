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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\DateHistogram as HistogramBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\DateHistogram as HistogramBucket;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter date histogram aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class DateHistogramTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a histogram aggregation from a bucket.
     */
    public function testBasicAggregationBuild(): void
    {
        $aggBuilder = $this->getHistogramAggregationBuilder();
        $bucket = new HistogramBucket('aggregationName', 'fieldName');

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('date_histogram', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_histogram']['field']);
        $this->assertEquals('1d', $aggregation['date_histogram']['interval']);
        $this->assertEquals(0, $aggregation['date_histogram']['min_doc_count']);
    }

    /**
     * Build a histogram aggregation from a bucket.
     */
    public function testComplexeAggregationBuild(): void
    {
        $aggBuilder = $this->getHistogramAggregationBuilder();
        $bucket = new HistogramBucket(
            'aggregationName',
            'fieldName',
            [],
            [],
            [],
            null,
            null,
            null,
            '2y',
            10,
            ['min' => 2008, 'max' => 2050]
        );

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('date_histogram', $aggregation);
        $this->assertEquals('fieldName', $aggregation['date_histogram']['field']);
        $this->assertEquals('2y', $aggregation['date_histogram']['interval']);
        $this->assertEquals(10, $aggregation['date_histogram']['min_doc_count']);
        $this->assertEquals(['min' => 2008, 'max' => 2050], $aggregation['date_histogram']['extended_bounds']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = $this->getHistogramAggregationBuilder();
        $this->expectExceptionMessage('Query builder : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->buildBucket($termsBucket);
    }
    /**
     * Get the histogram builder used in tests.
     *
     * @return HistogramBuilder
     */
    private function getHistogramAggregationBuilder()
    {
        return new HistogramBuilder();
    }
}
