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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\Histogram as HistogramBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\Histogram as HistogramBucket;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter histogram aggregation builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class HistogramTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build an histogram aggregation from a bucket.
     *
     * @return void
     */
    public function testHistogramAggregationBuild()
    {
        $aggBuilder = $this->getHistogramAggregationBuilder();
        $bucket     = new HistogramBucket('aggregationName', 'fieldName', []);

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('histogram', $aggregation);
        $this->assertEquals('fieldName', $aggregation['histogram']['field']);
        $this->assertEquals(1, $aggregation['histogram']['interval']);
        $this->assertEquals(0, $aggregation['histogram']['min_doc_count']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     *
     * @return void
     */
    public function testInvalidBucketAggregationBuild()
    {
        $this->expectExceptionMessage("Query builder : invalid aggregation type invalidType.");
        $this->expectException(\InvalidArgumentException::class);
        $termBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termBucket->method('getType')->will($this->returnValue('invalidType'));

        $this->getHistogramAggregationBuilder()->buildBucket($termBucket);
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
