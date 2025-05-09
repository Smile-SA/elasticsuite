<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\Aggregation\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder\Metric as MetricBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\Metric as MetricBucket;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Search adapter top level metrics aggregation builder test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class MetricTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the basic metric aggregation building.
     *
     * @return void
     */
    public function testBasicMetricAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationBuilder();
        $metricBucket = new MetricBucket(
            'aggregationName',
            'aggregationField'
        );

        $aggregation = $aggBuilder->buildBucket($metricBucket);
        $this->assertEquals(MetricInterface::TYPE_STATS, $metricBucket->getMetricType());
        $this->assertArrayHasKey($metricBucket->getMetricType(), $aggregation);
        $this->assertArrayHasKey('field', $aggregation[$metricBucket->getMetricType()]);
        $this->assertEquals('aggregationField', $aggregation[$metricBucket->getMetricType()]['field']);
    }

    /**
     * Test the basic metric aggregation building.
     *
     * @return void
     */
    public function testBasicMetricConfigAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationBuilder();
        $metricBucket = new MetricBucket(
            'aggregationName',
            'aggregationField',
            [],
            [],
            [],
            null,
            null,
            null,
            MetricInterface::TYPE_PERCENTILES,
            ['values' => [500, 600]]
        );

        $aggregation = $aggBuilder->buildBucket($metricBucket);
        $this->assertEquals(MetricInterface::TYPE_PERCENTILES, $metricBucket->getMetricType());
        $this->assertArrayHasKey($metricBucket->getMetricType(), $aggregation);
        $this->assertArrayHasKey('field', $aggregation[$metricBucket->getMetricType()]);
        $this->assertEquals('aggregationField', $aggregation[$metricBucket->getMetricType()]['field']);
        $this->assertArrayHasKey('values', $aggregation[$metricBucket->getMetricType()]);
        $this->assertEquals([500, 600], $aggregation[$metricBucket->getMetricType()]['values']);
    }

    /**
     * Test the metric aggregation building with a script.
     *
     * @return void
     */
    public function testScriptMetricAggregationBuild(): void
    {
        $aggBuilder = $this->getAggregationBuilder();
        $metricBucket = new MetricBucket(
            'aggregationName',
            'aggregationField',
            [],
            [],
            [],
            null,
            null,
            null,
            MetricInterface::TYPE_AVG,
            ['script' => '_score']
        );

        $aggregation = $aggBuilder->buildBucket($metricBucket);
        $this->assertEquals(MetricInterface::TYPE_AVG, $metricBucket->getMetricType());
        $this->assertArrayHasKey($metricBucket->getMetricType(), $aggregation);

        $this->assertArrayNotHasKey('field', $aggregation[$metricBucket->getMetricType()]);
        $this->assertArrayHasKey('script', $aggregation[$metricBucket->getMetricType()]);
        $this->assertEquals('_score', $aggregation[$metricBucket->getMetricType()]['script']);
    }

    /**
     * Test an exception is thrown when using the metric aggs builder with another bucket type.
     *
     * @return void
     */
    public function testInvalidMetricAggregationBuild(): void
    {
        $this->expectExceptionMessage("Query builder : invalid aggregation type invalidType.");
        $this->expectException(\InvalidArgumentException::class);
        $metricBucket = $this->getMockBuilder(BucketInterface::class)->getMock();
        $metricBucket->method('getType')->will($this->returnValue('invalidType'));

        $this->getAggregationBuilder()->buildBucket($metricBucket);
    }

    /**
     * Aggregation builder used in tests.
     *
     * @return MetricBuilder
     */
    private function getAggregationBuilder(): MetricBuilder
    {
        return new MetricBuilder();
    }
}
