<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule\Condition;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryDependencyRecorderInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Not;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine;

/**
 * Virtual-category Boolean semantics for an unavailable product attribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class CombineTest extends TestCase
{
    /**
     * Pin the parent Boolean structure, including NOT(false) under a FALSE parent.
     *
     * @dataProvider combineProvider
     * @param string $aggregator Parent aggregator.
     * @param bool   $value      Parent truth value.
     * @param string $clause     Expected Boolean clause.
     * @return void
     */
    public function testCombineWithUnmappedCondition($aggregator, $value, $clause)
    {
        $attributeList = $this->createMock(AttributeList::class);
        $attributeList->method('hasField')->willReturnMap([['unmapped', false], ['brand', true]]);
        $attributeList->method('getField')->with('brand')
            ->willReturn(new Field('brand', FieldInterface::FIELD_TYPE_INTEGER));
        $factories = [];
        foreach ([
            QueryInterface::TYPE_TERMS => Terms::class,
            QueryInterface::TYPE_BOOL => Boolean::class,
            QueryInterface::TYPE_NOT => Not::class,
        ] as $type => $class) {
            $factory = $this->createMock(ObjectManagerInterface::class);
            $factory->method('create')->willReturnCallback(function ($params) use ($class) {
                return new $class(...$params);
            });
            $factories[$type] = $factory;
        }
        $queryFactory = new QueryFactory($factories);
        $builder = new QueryBuilder(
            $attributeList,
            $queryFactory,
            new SpecialAttributesProvider(),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class),
            $this->createMock(LoggerInterface::class)
        );
        $conditions = [];
        $children = [];
        foreach (['unmapped', 'brand'] as $attribute) {
            $condition = $this->createMock(Product::class);
            $condition->method('getValue')->willReturn('7');
            $condition->method('getInputType')->willReturn('select');
            $condition->method('__call')->willReturnMap([
                ['getAttribute', [], $attribute],
                ['getOperator', [], '=='],
            ]);
            $children[] = $builder->getSearchQuery($condition);
            $condition->method('getSearchQuery')->willReturn(end($children));
            $conditions[] = $condition;
        }
        $combine = $this->getMockBuilder(Combine::class)->disableOriginalConstructor()
            ->onlyMethods(['getConditions', 'getValue', '__call', 'asStringRecursive'])->getMock();
        $combine->method('getConditions')->willReturn($conditions);
        $combine->method('getValue')->willReturn($value);
        $combine->method('__call')->willReturnMap([['getAggregator', [], $aggregator]]);
        $property = new \ReflectionProperty(Combine::class, 'queryFactory');
        $property->setAccessible(true);
        $property->setValue($combine, $queryFactory);

        $query = $combine->getSearchQuery();
        $clauses = $clause === 'must' ? $query->getMust() : $query->getShould();
        $this->assertCount(2, $clauses);
        foreach ($clauses as $index => $child) {
            if (!$value) {
                $this->assertInstanceOf(Not::class, $child);
                $child = $child->getQuery();
            }
            $this->assertSame($children[$index], $child);
        }
        $this->assertSame([], $children[0]->getValues());
        $this->assertSame('entity_id', $children[0]->getField());
        $this->assertSame('brand', $children[1]->getField());
        $this->assertSame('7', $children[1]->getValues()[0]);
    }

    /**
     * TRUE ALL = false; TRUE ANY = mapped; FALSE ALL = NOT(mapped); FALSE ANY = true.
     *
     * @return array
     */
    public static function combineProvider(): array
    {
        return [
            'ALL TRUE' => ['all', true, 'must'],
            'ANY TRUE' => ['any', true, 'should'],
            'ALL FALSE' => ['all', false, 'must'],
            'ANY FALSE' => ['any', false, 'should'],
        ];
    }
}
