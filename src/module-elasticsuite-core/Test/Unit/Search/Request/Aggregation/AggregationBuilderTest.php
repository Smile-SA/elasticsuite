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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder as QueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory;

/**
 * Search request aggregation builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test building aggregations for simple fields.
     *
     * @return void
     */
    public function testSimpleAggBuilder()
    {
        $builder = new AggregationBuilder(
            $this->getAggregationFactory(),
            $this->getMetricFactory(),
            $this->getPipelineFactory(),
            $this->getQueryBuilder()
        );

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            ['name' => 'simpleField', 'type' => 'aggType', 'foo' => 'bar'],
            ['name' => 'searchableField', 'type' => 'aggType', 'foo' => 'bar'],
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(2, $buckets);

        $this->assertEquals('simpleField', $buckets[0]['field']);
        $this->assertEquals('simpleField', $buckets[0]['name']);
        $this->assertEquals('bar', $buckets[0]['foo']);

        $this->assertEquals('searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('searchableField', $buckets[1]['name']);
        $this->assertEquals('bar', $buckets[1]['foo']);
    }

    /**
     * Test building filtered aggregations for simple fields.
     *
     * @return void
     */
    public function testFilteredAggBuilder()
    {
        $builder = new AggregationBuilder(
            $this->getAggregationFactory(),
            $this->getMetricFactory(),
            $this->getPipelineFactory(),
            $this->getQueryBuilder()
        );

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            ['name' => 'simpleField', 'type' => 'aggType', 'foo' => 'bar'],
            ['name' => 'searchableField', 'type' => 'aggType', 'foo' => 'bar', 'nestedPath' => 'invalidNestedPath'],
        ];

        $filters = [
            'simpleField'     => 'simpleFieldFilter',
            'searchableField' => 'searchableFieldFilter',
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, $filters);

        $this->assertCount(2, $buckets);

        $this->assertEquals('simpleField', $buckets[0]['field']);
        $this->assertEquals('simpleField', $buckets[0]['name']);
        $this->assertEquals('bar', $buckets[0]['foo']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['filter']);

        $this->assertEquals('searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('searchableField', $buckets[1]['name']);
        $this->assertEquals('bar', $buckets[1]['foo']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[1]['filter']);
    }

    /**
     * Test building aggregations for nested fields.
     *
     * @return void
     */
    public function testNestedAggBuilder()
    {
        $builder = new AggregationBuilder(
            $this->getAggregationFactory(),
            $this->getMetricFactory(),
            $this->getPipelineFactory(),
            $this->getQueryBuilder()
        );

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            ['name' => 'nested.simpleField', 'type' => 'aggType', 'foo' => 'bar'],
            ['name' => 'nested.searchableField', 'type' => 'aggType', 'foo' => 'bar', 'nestedPath' => 'invalidNestedPath'],
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(2, $buckets);

        $this->assertEquals('nested.simpleField', $buckets[0]['field']);
        $this->assertEquals('nested.simpleField', $buckets[0]['name']);
        $this->assertEquals('nested', $buckets[1]['nestedPath']);
        $this->assertEquals('bar', $buckets[0]['foo']);

        $this->assertEquals('nested.searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('nested.searchableField', $buckets[1]['name']);
        $this->assertEquals('nested', $buckets[1]['nestedPath']);
        $this->assertEquals('bar', $buckets[1]['foo']);
    }

    /**
     * Test building aggregations for nested fields when using filters.
     *
     * @return void
     */
    public function testNestedFilteredAggBuilder()
    {
        $builder = new AggregationBuilder(
            $this->getAggregationFactory(),
            $this->getMetricFactory(),
            $this->getPipelineFactory(),
            $this->getQueryBuilder()
        );

        $containerConfig = $this->getContainerConfiguration();
        $aggregations    = [
            [
                'name' => 'nested.simpleField',
                'type' => 'aggType',
                'nestedFilter' => ['nested.searchableField' => 'simpleNestedFieldFilter'],
            ],
        ];

        $filters = ['simpleField' => 'simpleFieldFilter'];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, $filters);

        $this->assertCount(1, $buckets);

        $this->assertEquals('nested.simpleField', $buckets[0]['field']);
        $this->assertEquals('nested.simpleField', $buckets[0]['name']);
        $this->assertEquals('nested', $buckets[0]['nestedPath']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['nestedFilter']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['filter']);
    }

    /**
     * Search request query builder used during test.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder
     */
    private function getQueryBuilder()
    {
        $queryMock = $this->getMockBuilder(QueryInterface::class)->getMock();

        $queryFactory = $this->getMockBuilder(QueryFactory::class)->disableOriginalConstructor()->getMock();
        $queryFactory->method('create')->will($this->returnValue($queryMock));

        return new \Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder($queryFactory);
    }

    /**
     * Aggregation factory used during tests.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getAggregationFactory()
    {
        $aggregationFactory = $this->getMockBuilder(AggregationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aggregationFactory->method('create')->will($this->returnArgument(1));

        return $aggregationFactory;
    }

    /**
     * Container configuration used during tests.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getContainerConfiguration()
    {
        $containerConfig = $this->getMockBuilder(ContainerConfigurationInterface::class)->getMock();

        $mapping = $this->getMapping();
        $containerConfig->method('getMapping')
            ->will($this->returnValue($mapping));

        return $containerConfig;
    }

    /**
     * Mapping used during tests.
     *
     * @return \Smile\ElasticsuiteCore\Index\Mapping
     */
    private function getMapping()
    {
        $fields = [
            new Field('entity_id', Field::FIELD_TYPE_INTEGER),
            new Field('simpleField', Field::FIELD_TYPE_KEYWORD),
            new Field('searchableField', Field::FIELD_TYPE_TEXT, null, ['is_searchable' => true]),
            new Field('nested.simpleField', Field::FIELD_TYPE_KEYWORD, 'nested'),
            new Field('nested.searchableField', Field::FIELD_TYPE_TEXT, 'nested', ['is_searchable' => true]),
            new Field('notFilterableField', Field::FIELD_TYPE_TEXT, null, ['is_filterable' => false, 'is_searchable' => true]),
        ];

        return new Mapping('entity_id', $fields);
    }

    /**
     * Metrics factory used during tests.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMetricFactory()
    {
        return $this->getMockBuilder(MetricFactory::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * Pipeline aggregation factory used during tests.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getPipelineFactory()
    {
        return $this->getMockBuilder(PipelineFactory::class)->disableOriginalConstructor()->getMock();
    }
}
