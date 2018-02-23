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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\Query\Builder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory;

/**
 * Search request query builder test case.
 *
 * @category  Smile_Elasticsuite
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
        $builder = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            'simpleField'            => ['type' => 'aggType', 'config' => ['foo' => 'bar']],
            'searchableField'        => ['type' => 'aggType', 'config' => ['foo' => 'bar']],
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(2, $buckets);

        $this->assertEquals('simpleField', $buckets[0]['field']);
        $this->assertEquals('simpleField', $buckets[0]['name']);
        $this->assertEquals([], $buckets[0]['metrics']);
        $this->assertEquals('bar', $buckets[0]['foo']);

        $this->assertEquals('searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('searchableField', $buckets[1]['name']);
        $this->assertEquals([], $buckets[1]['metrics']);
        $this->assertEquals('bar', $buckets[1]['foo']);
    }

    /**
     * Test building filtered aggregations for simple fields.
     *
     * @return void
     */
    public function testFilteredAggBuilder()
    {
        $builder = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            'simpleField'            => ['type' => 'aggType', 'config' => ['foo' => 'bar']],
            'searchableField'        => ['type' => 'aggType', 'config' => ['foo' => 'bar', 'nestedPath' => 'invalidNestedPath']],
        ];

        $filters = [
            'simpleField'            => 'simpleFieldFilter',
            'searchableField'        => 'searchableFieldFilter',
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, $filters);

        $this->assertCount(2, $buckets);

        $this->assertEquals('simpleField', $buckets[0]['field']);
        $this->assertEquals('simpleField', $buckets[0]['name']);
        $this->assertEquals([], $buckets[0]['metrics']);
        $this->assertEquals('bar', $buckets[0]['foo']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['filter']);

        $this->assertEquals('searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('searchableField', $buckets[1]['name']);
        $this->assertEquals([], $buckets[1]['metrics']);
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
        $builder = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());

        $containerConfig = $this->getContainerConfiguration();
        $aggregations           = [
            'nested.simpleField' => ['type' => 'aggType', 'config' => ['foo' => 'bar']],
            'nested.searchableField' => ['type' => 'aggType', 'config' => ['foo' => 'bar', 'nestedPath' => 'invalidNestedPath']],
        ];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(2, $buckets);

        $this->assertEquals('nested.simpleField', $buckets[0]['field']);
        $this->assertEquals('nested.simpleField', $buckets[0]['name']);
        $this->assertEquals([], $buckets[0]['metrics']);
        $this->assertEquals('nested', $buckets[1]['nestedPath']);
        $this->assertEquals('bar', $buckets[0]['foo']);

        $this->assertEquals('nested.searchableField.untouched', $buckets[1]['field']);
        $this->assertEquals('nested.searchableField', $buckets[1]['name']);
        $this->assertEquals([], $buckets[1]['metrics']);
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
        $builder = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());

        $containerConfig = $this->getContainerConfiguration();
        $aggregations    = [
            'nested.simpleField' => [
                'type' => 'aggType',
                'config' => ['nestedFilter' => ['nested.searchableField' => 'simpleNestedFieldFilter']],
            ],
        ];

        $filters = ['simpleField' => 'simpleFieldFilter'];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, $filters);

        $this->assertCount(1, $buckets);

        $this->assertEquals('nested.simpleField', $buckets[0]['field']);
        $this->assertEquals('nested.simpleField', $buckets[0]['name']);
        $this->assertEquals([], $buckets[0]['metrics']);
        $this->assertEquals('nested', $buckets[0]['nestedPath']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['nestedFilter']);
        $this->assertInstanceOf(QueryInterface::class, $buckets[0]['filter']);
    }

    /**
     * Test building aggregations using a field not present into the mapping.
     *
     * @return void
     */
    public function testUnknownFieldAggregation()
    {
        $builder         = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());
        $containerConfig = $this->getContainerConfiguration();
        $aggregations    = ['invalidField' => ['type' => 'aggType', 'config' => ['foo' => 'bar', 'metrics' => []]]];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(1, $buckets);

        $this->assertEquals($aggregations['invalidField']['config'], $buckets[0]);
    }

    /**
     * Test building aggregation using a field that is not filetrable.
     *
     * @return void
     */
    public function testNotFilterableFieldAggregation()
    {
        $builder = new AggregationBuilder($this->getAggregationFactory(), $this->getMetricFactory(), $this->getQueryBuilder());

        $containerConfig = $this->getContainerConfiguration();
        $aggregations    = ['notFilterableField' => ['type' => 'aggType', 'config' => ['foo' => 'bar', 'metrics' => []]]];

        $buckets = $builder->buildAggregations($containerConfig, $aggregations, []);

        $this->assertCount(1, $buckets);
        $this->assertEquals($aggregations['notFilterableField']['config'], $buckets[0]);
    }

    /**
     * Search request query builder used during test.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\Builder
     */
    private function getQueryBuilder()
    {
        $queryMock = $this->getMockBuilder(QueryInterface::class)
            ->setMethods(['getQuery', 'getName', 'getType', 'getBoost'])
            ->getMock();
        $queryMock->method('getQuery')->will($this->returnSelf());

        $queryFactory = $this->getMockBuilder(QueryFactory::class)->disableOriginalConstructor()->getMock();
        $queryFactory->method('create')->will($this->returnValue($queryMock));

        $fulltextQueryBuilder = new \Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder($queryFactory);
        $filteredQueryBuilder = new \Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder($queryFactory);

        return new QueryBuilder($queryFactory, $fulltextQueryBuilder, $filteredQueryBuilder);
    }

    /**
     * Aggregation factory used during tests.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
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
     * @return PHPUnit_Framework_MockObject_MockObject
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
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getMetricFactory()
    {
        return $this->getMockBuilder(MetricFactory::class)->getMock();
    }
}
