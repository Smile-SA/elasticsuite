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

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\Term as TermBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\Term as TermBucket;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter term aggregation builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TermTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the standard term aggregation building.
     *
     * @return void
     */
    public function testBasicTermAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $termBucket = new TermBucket('aggregationName', 'fieldName', []);

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals(TermBucket::MAX_BUCKET_SIZE, $aggregation['terms']['size']);
        $this->assertEquals([TermBucket::SORT_ORDER_COUNT => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation building sorted by alphabetic order.
     *
     * @return void
     */
    public function testAplhabeticSortOrderTermAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $termBucket = new TermBucket(
            'aggregationName',
            'fieldName',
            [],
            [],
            [],
            null,
            null,
            null,
            1,
            TermBucket::SORT_ORDER_TERM
        );

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals([TermBucket::SORT_ORDER_TERM => SortOrderInterface::SORT_ASC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation building sorted by alphabetic order using the deprecated sort order.
     *
     * @return void
     */
    public function testAplhabeticSortOrderTermDeprecatedAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $termBucket = new TermBucket(
            'aggregationName',
            'fieldName',
            [],
            [],
            [],
            null,
            null,
            null,
            1,
            TermBucket::SORT_ORDER_TERM_DEPRECATED
        );

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals([TermBucket::SORT_ORDER_TERM => SortOrderInterface::SORT_ASC], $aggregation['terms']['order']);
    }

    /**
     * Test the standard term aggregation building sorted by relevance.
     *
     * @return void
     */
    public function testRelevanceSortOrderTermAggregationBuild()
    {
        $aggBuilder = $this->getAggregationBuilder();
        $termBucket = new TermBucket('aggregationName', 'fieldName', [], [], [], null, null, null, 1, TermBucket::SORT_ORDER_RELEVANCE);

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals(['termRelevance' => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
        $this->assertArrayHasKey('aggregations', $aggregation);
        $this->assertArrayHasKey('termRelevance', $aggregation['aggregations']);
        $this->assertEquals(['avg' => ['script' => TermBucket::SORT_ORDER_RELEVANCE]], $aggregation['aggregations']['termRelevance']);
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

        $this->getAggregationBuilder()->buildBucket($termBucket);
    }

    /**
     * Test the max bucket size limitation.
     *
     * @dataProvider sizeDataProvider
     *
     * @param integer $size     Configured bucket size.
     * @param integer $expected Expected bucket size in the built aggregation.
     *
     * @return void
     */
    public function testBucketSize($size, $expected)
    {
        $aggBuilder = $this->getAggregationBuilder();
        $termBucket = new TermBucket('aggregationName', 'fieldName', [], [], [], null, null, null, $size);

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertEquals($expected, $aggregation['terms']['size']);
    }

    /**
     * Dataset used to run testBucketSize.
     *
     * @return array
     */
    public function sizeDataProvider()
    {
        return [
            [0, TermBucket::MAX_BUCKET_SIZE],
            [TermBucket::MAX_BUCKET_SIZE - 1, TermBucket::MAX_BUCKET_SIZE - 1],
            [TermBucket::MAX_BUCKET_SIZE, TermBucket::MAX_BUCKET_SIZE],
            [TermBucket::MAX_BUCKET_SIZE + 1, TermBucket::MAX_BUCKET_SIZE],
        ];
    }

    /**
     * Aggregation builder used in tests.
     *
     * @return TermBuilder
     */
    private function getAggregationBuilder()
    {
        return new TermBuilder();
    }
}
