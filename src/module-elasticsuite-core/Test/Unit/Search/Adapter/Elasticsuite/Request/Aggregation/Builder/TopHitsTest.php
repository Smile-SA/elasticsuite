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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\TopHits as TopHitsBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\Term as TermBucket;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\TopHits as TopHitsBucket;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Search adapter top hits aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class TopHitsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the max bucket size limitation.
     *
     * @dataProvider sizeDataProvider
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param integer $size     Configured bucket size.
     * @param integer $hasSize  Will the built bucket have a size param.
     * @param integer $expected Expected bucket size in the built aggregation.
     *
     * @return void
     */
    public function testBucketNoSize($size, $hasSize, $expected)
    {
        $aggBuilder = $this->getAggregationBuilder();
        $topHitsBucket = new TopHitsBucket(
            'aggregationName',
            [],
            $size
        );

        $aggregation = $aggBuilder->buildBucket($topHitsBucket);
        $this->assertArrayHasKey('top_hits', $aggregation);
        if ($hasSize) {
            $this->assertArrayHasKey('size', $aggregation['top_hits']);
            $this->assertEquals($expected, $aggregation['top_hits']['size']);
        } else {
            $this->assertArrayNotHasKey('size', $aggregation['top_hits']);
        }
    }

    /**
     * Test the bucket default max size.
     *
     * @return void
     */
    public function testBucketDefaultSize()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $topHitsBucket = new TopHitsBucket(
            'aggregationName',
            []
        );

        $aggregation = $aggBuilder->buildBucket($topHitsBucket);
        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayHasKey('size', $aggregation['top_hits']);
        $this->assertEquals(1, $aggregation['top_hits']['size']);
    }

    /**
     * Dataset used to run testBucketSize.
     *
     * @return array
     */
    public function sizeDataProvider()
    {
        return [
            [0, false, 0],
            [TermBucket::MAX_BUCKET_SIZE - 1, true, TermBucket::MAX_BUCKET_SIZE - 1],
            [TermBucket::MAX_BUCKET_SIZE, true, TermBucket::MAX_BUCKET_SIZE],
            [TermBucket::MAX_BUCKET_SIZE + 1, true, TermBucket::MAX_BUCKET_SIZE + 1],
        ];
    }

    /**
     * Test the bucket default max size.
     *
     * @return void
     */
    public function testTopHitsSourceInnerHits()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $topHitsBucket = new TopHitsBucket(
            'aggregationName'
        );

        $aggregation = $aggBuilder->buildBucket($topHitsBucket);
        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayNotHasKey('_source', $aggregation['top_hits']);

        $topHitsBucket = new TopHitsBucket(
            'aggregationName',
            ['name', 'sku']
        );
        $aggregation = $aggBuilder->buildBucket($topHitsBucket);
        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayHasKey('_source', $aggregation['top_hits']);
        $this->assertArrayHasKey('includes', $aggregation['top_hits']['_source']);
        $this->assertEquals(['name', 'sku'], $aggregation['top_hits']['_source']['includes']);
    }

    /**
     * Test the standard term aggregation building sorted by provided params.
     *
     * @return void
     */
    public function testComplexSortOrderTopHitsAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $topHitsBucket = new TopHitsBucket(
            'aggregationName',
            [],
            1,
            ['sortField' => SortOrderInterface::SORT_DESC]
        );

        $aggregation = $aggBuilder->buildBucket($topHitsBucket);

        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertEquals(['sortField' => SortOrderInterface::SORT_DESC], $aggregation['top_hits']['sort']);
    }

    /**
     * Test the standard term aggregation building sorted basic/scalar provided param.
     *
     * @return void
     */
    public function testSimpleSortOrderTopHitsAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $topHitsBucket = new TopHitsBucket(
            'aggregationName',
            [],
            1,
            BucketInterface::SORT_ORDER_RELEVANCE
        );
        $aggregation = $aggBuilder->buildBucket($topHitsBucket);

        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayHasKey('sort', $aggregation['top_hits']);
        $this->assertEquals([BucketInterface::SORT_ORDER_RELEVANCE => SortOrderInterface::SORT_DESC], $aggregation['top_hits']['sort']);

        $topHitsNestedBucket = new TopHitsBucket(
            'aggregationName',
            [],
            1,
            BucketInterface::SORT_ORDER_RELEVANCE,
            [],
            [],
            [],
            'nestedPath'
        );
        $aggregation = $aggBuilder->buildBucket($topHitsNestedBucket);

        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayNotHasKey('sort', $aggregation['top_hits']);


        $topHitsUnsupportedSortBucket = new TopHitsBucket(
            'aggregationName',
            [],
            1,
            BucketInterface::SORT_ORDER_COUNT,
            [],
            [],
            [],
            'nestedPath'
        );
        $aggregation = $aggBuilder->buildBucket($topHitsUnsupportedSortBucket);

        $this->assertArrayHasKey('top_hits', $aggregation);
        $this->assertArrayNotHasKey('sort', $aggregation['top_hits']);
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
        $topHitsBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $topHitsBucket->method('getType')->will($this->returnValue('invalidType'));

        $this->getAggregationBuilder()->buildBucket($topHitsBucket);
    }

    /**
     * Aggregation builder used in tests.
     *
     * @return TopHitsBuilder
     */
    private function getAggregationBuilder()
    {
        return new TopHitsBuilder();
    }
}
