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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\QueryGroup as QueryGroupBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\QueryGroup as QueryGroupBucket;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Search adapter query group aggregation builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryGroupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Build an query group aggregation from a bucket.
     *
     * @return void
     */
    public function testBasicTermAggregationBuild()
    {
        $queries    = [
            'filter1' => $this->getMockBuilder(QueryInterface::class)->getMock(),
            'filter2' => $this->getMockBuilder(QueryInterface::class)->getMock(),
        ];
        $queries['filter1']->method('getName')->will($this->returnValue('filter1'));
        $queries['filter2']->method('getName')->will($this->returnValue('filter2'));
        $bucket      = new QueryGroupBucket('aggregationName', $queries, []);
        $aggregation = $this->getQueryGroupAggregationBuilder()->buildBucket($bucket);

        $this->assertCount(2, $aggregation['filters']['filters']);
        $this->assertEquals('filter1', $aggregation['filters']['filters']['filter1']);
        $this->assertEquals('filter2', $aggregation['filters']['filters']['filter2']);
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

        $this->getQueryGroupAggregationBuilder()->buildBucket($termBucket);
    }

    /**
     * Get the query group builder used in tests.
     *
     * @return QueryGroupBuilder
     */
    private function getQueryGroupAggregationBuilder()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $buildQueryCallback = function (QueryInterface $query) {
            return $query->getName();
        };

        $queryBuilder->method('buildQuery')->will($this->returnCallback($buildQueryCallback));

        return new QueryGroupBuilder($queryBuilder);
    }
}
