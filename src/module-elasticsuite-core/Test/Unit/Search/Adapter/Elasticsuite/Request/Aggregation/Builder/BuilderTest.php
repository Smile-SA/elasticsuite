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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder as AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\BuilderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Search adapter query builder test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildSimpleAggregations()
    {
        $buckets = [
            $this->createBucket('aggregation1', 'bucketType'),
            $this->createBucket('aggregation2', 'bucketType'),
        ];

        $aggregations = $this->getAggregationBuilder()->buildAggregations($buckets);
        $this->assertCount(2, $aggregations);

        for ($i = 1; $i <=2; $i++) {
            $aggregationName = sprintf('aggregation%s', $i);
            $aggregation     = $this->getAggregationByName($aggregations, $aggregationName);
            $this->assertEquals(['type' => 'bucketType'], $aggregation);
        }
    }

    public function testBuildNestedAggregation()
    {
        $buckets = [$this->createNestedBucket('aggregation', 'bucketType')];
        $aggregations = $this->getAggregationBuilder()->buildAggregations($buckets);

        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertArrayHasKey('nested', $aggregation);
        $this->assertArrayHasKey('path', $aggregation['nested']);
        $this->assertEquals('parent', $aggregation['nested']['path']);
        $this->assertCount(2, $aggregation);

        $aggregations = $this->getSubAggregartions($aggregation);
        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertEquals(['type' => 'bucketType'], $aggregation);
    }

    public function testBuildFilteredNestedAggregation()
    {
        $buckets = [$this->createFilteredNestedBucket('aggregation', 'bucketType')];
        $aggregations = $this->getAggregationBuilder()->buildAggregations($buckets);

        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertArrayHasKey('nested', $aggregation);
        $this->assertArrayHasKey('path', $aggregation['nested']);
        $this->assertEquals('parent', $aggregation['nested']['path']);

        $aggregations = $this->getSubAggregartions($aggregation);
        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertArrayHasKey('filter', $aggregation);
        $this->assertEquals('query', $aggregation['filter']);
        $this->assertCount(2, $aggregation);

        $aggregations = $this->getSubAggregartions($aggregation);
        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertEquals(['type' => 'bucketType'], $aggregation);
    }

    public function testBuildFilteredAggregation()
    {
        $buckets = [$this->createFilteredBucket('aggregation', 'bucketType')];
        $aggregations = $this->getAggregationBuilder()->buildAggregations($buckets);

        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertArrayHasKey('filter', $aggregation);
        $this->assertEquals('query', $aggregation['filter']);
        $this->assertCount(2, $aggregation);

        $aggregations = $this->getSubAggregartions($aggregation);
        $aggregation  = $this->getAggregationByName($aggregations, 'aggregation');
        $this->assertEquals(['type' => 'bucketType'], $aggregation);
    }

    /**
     * Test an exception is thrown when trying to build a bucket which is not handled by the builder.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No builder found for aggregation type invalidBucketType.
     *
     * @return void
     */
    public function testBuildInvalidAggregation()
    {
        $buckets = [$this->createNestedBucket('aggregation', 'invalidBucketType')];
        $this->getAggregationBuilder()->buildAggregations($buckets);
    }

    /**
     * @return AggregationBuilder
     */
    private function getAggregationBuilder()
    {
        $queryBuilder = $this->getQueryBuilder();
        $aggregationBuilderMock = $this->getMockBuilder(BuilderInterface::class)->getMock();

        $buildBucketCallback = function (BucketInterface $bucket) {
            return ['type' => $bucket->getType()];
        };
        $aggregationBuilderMock->method('buildBucket')->will($this->returnCallback($buildBucketCallback));

        return new AggregationBuilder($queryBuilder, ['bucketType' => $aggregationBuilderMock]);
    }

    /**
     *
     * @param unknown $name
     * @param unknown $type
     * @param unknown $nestedPath
     * @param string $filtered
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function createBucket($name, $type)
    {
        $bucket = $this->getMockBuilder(BucketInterface::class)->getMock();

        $bucket->method('getName')->will($this->returnValue($name));
        $bucket->method('getType')->will($this->returnValue($type));

        return $bucket;
    }

    private function createNestedBucket($name, $type)
    {
        $bucket = $this->createBucket($name, $type);
        $bucket->method('isNested')->will($this->returnValue(true));
        $bucket->method('getNestedPath')->will($this->returnValue('parent'));

        return $bucket;
    }

    private function createFilteredNestedBucket($name, $type)
    {
        $filter = $this->getMockBuilder(QueryInterface::class)->getMock();
        $bucket = $this->createBucket($name, $type);
        $bucket->method('isNested')->will($this->returnValue(true));
        $bucket->method('getNestedPath')->will($this->returnValue('parent'));
        $bucket->method('getNestedFilter')->will($this->returnValue($filter));

        return $bucket;
    }

    private function createFilteredBucket($name, $type)
    {
        $filter = $this->getMockBuilder(QueryInterface::class)->getMock();
        $bucket = $this->createBucket($name, $type);
        $bucket->method('getFilter')->will($this->returnValue($filter));

        return $bucket;
    }

    /**
     * Mock a query builder.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder
     */
    private function getQueryBuilder()
    {
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->getMock();
        $queryBuilderMock->method('buildQuery')->will($this->returnValue('query'));

        return $queryBuilderMock;
    }

    private function processSimpleAggregartionAssertions($aggregationName)
    {
        $this->assertArrayHasKey($aggregationName, $subAggregations);
        $this->assertEquals(['type' => 'bucketType'], $subAggregations[$aggregationName]);
    }

    private function getAggregationByName($aggregations, $aggregationName)
    {
        $this->assertArrayHasKey($aggregationName, $aggregations);

        return $aggregations[$aggregationName];
    }

    private function getSubAggregartions($aggregation, $expectedCount = 1)
    {
        $this->assertArrayHasKey('aggregations', $aggregation);
        $this->assertCount($expectedCount, $aggregation['aggregations']);

        return $aggregation['aggregations'];
    }
}
