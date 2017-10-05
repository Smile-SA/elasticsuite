<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
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
 * @category  Smile_Elasticsuite
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
        $this->assertEquals(0, $aggregation['terms']['size']);
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
        $termBucket = new TermBucket('aggregationName', 'fieldName', [], null, null, null, 10, TermBucket::SORT_ORDER_TERM);

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals(10, $aggregation['terms']['size']);
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
        $termBucket = new TermBucket('aggregationName', 'fieldName', [], null, null, null, 10, TermBucket::SORT_ORDER_RELEVANCE);

        $aggregation = $aggBuilder->buildBucket($termBucket);

        $this->assertArrayHasKey('terms', $aggregation);
        $this->assertEquals('fieldName', $aggregation['terms']['field']);
        $this->assertEquals(10, $aggregation['terms']['size']);
        $this->assertEquals(['termRelevance' => SortOrderInterface::SORT_DESC], $aggregation['terms']['order']);
        $this->assertArrayHasKey('aggregations', $aggregation);
        $this->assertArrayHasKey('termRelevance', $aggregation['aggregations']);
        $this->assertEquals(['avg' => ['script' => TermBucket::SORT_ORDER_RELEVANCE]], $aggregation['aggregations']['termRelevance']);
    }

    /**
     * Test an exception is thrown when using the term aggs builder with another bucket type.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Query builder : invalid aggregation type invalidType.
     *
     * @return void
     */
    public function testInvalidBucketAggregationBuild()
    {
        $termBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $termBucket->method('getType')->will($this->returnValue('invalidType'));

        $this->getAggregationBuilder()->buildBucket($termBucket);
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
