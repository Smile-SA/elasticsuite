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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\ReverseNested as ReverseNestedBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\ReverseNested;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter reverse nested aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class ReverseNestedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build a reverse nested aggregation from a bucket.
     */
    public function testReverseNestedAggregationBuild(): void
    {
        $aggBuilder = new ReverseNestedBuilder();
        $bucket = new ReverseNested('aggregationName', 'fieldName');

        $aggregation = $aggBuilder->buildBucket($bucket);

        $this->assertArrayHasKey('reverse_nested', $aggregation);
        $this->assertIsObject($aggregation['reverse_nested']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     */
    public function testInvalidBucketAggregationBuild(): void
    {
        $aggBuilder = new ReverseNestedBuilder();
        $this->expectExceptionMessage('Query builder : invalid aggregation type invalidType.');
        $this->expectException(\InvalidArgumentException::class);
        $termsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termsBucket->method('getType')->willReturn('invalidType');

        $aggBuilder->buildBucket($termsBucket);
    }
}
