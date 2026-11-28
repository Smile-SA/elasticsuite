<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule\Condition\Product;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine;
use Smile\ElasticsuiteCatalog\Model\Attribute\LayeredNavAttributesProvider;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryDependencyRecorderInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\PriceNestedFilter;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\HasImage;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsDiscount;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsInStock;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\StockQty;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider;
use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Helper\Mapping as MappingHelper;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Common;
use Smile\ElasticsuiteCore\Search\Request\Query\Exists;
use Smile\ElasticsuiteCore\Search\Request\Query\Filtered;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;
use Smile\ElasticsuiteCore\Search\Request\Query\MatchPhrasePrefix;
use Smile\ElasticsuiteCore\Search\Request\Query\MatchQuery;
use Smile\ElasticsuiteCore\Search\Request\Query\Missing;
use Smile\ElasticsuiteCore\Search\Request\Query\MoreLikeThis;
use Smile\ElasticsuiteCore\Search\Request\Query\MultiMatch;
use Smile\ElasticsuiteCore\Search\Request\Query\Nested;
use Smile\ElasticsuiteCore\Search\Request\Query\Not;
use Smile\ElasticsuiteCore\Search\Request\Query\Prefix;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Range;
use Smile\ElasticsuiteCore\Search\Request\Query\Regexp;
use Smile\ElasticsuiteCore\Search\Request\Query\Term;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Query builder unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @var array
     */
    protected $queryTypes = [
        QueryInterface::TYPE_MATCH  => MatchQuery::class,
        QueryInterface::TYPE_BOOL   => Boolean::class,
        QueryInterface::TYPE_FILTER => Filtered::class,
        QueryInterface::TYPE_NESTED => Nested::class,
        QueryInterface::TYPE_RANGE  => Range::class,
        QueryInterface::TYPE_TERM   => Term::class,
        QueryInterface::TYPE_TERMS  => Terms::class,
        QueryInterface::TYPE_NOT    => Not::class,
        QueryInterface::TYPE_MULTIMATCH => MultiMatch::class,
        QueryInterface::TYPE_COMMON     => Common::class,
        QueryInterface::TYPE_EXISTS     => Exists::class,
        QueryInterface::TYPE_MISSING    => Missing::class,
        QueryInterface::TYPE_FUNCTIONSCORE  => FunctionScore::class,
        QueryInterface::TYPE_MORELIKETHIS   => MoreLikeThis::class,
        QueryInterface::TYPE_MATCHPHRASEPREFIX => MatchPhrasePrefix::class,
        QueryInterface::TYPE_PREFIX => Prefix::class,
        QueryInterface::TYPE_REGEXP => Regexp::class,
    ];

    /**
     * Missing fields must fail closed, including negative operators and special attribute fallbacks.
     *
     * @dataProvider unmappedAttributeProvider
     * @param string $attribute Attribute code.
     * @param string $operator  Condition operator.
     * @return void
     */
    public function testUnmappedAttributeMatchesNothing($attribute, $operator)
    {
        $attributeList = $this->getAdvancedAttributeList([
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
        ]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            sprintf(
                'Product attribute "%s" used in a rule condition is not indexed '
                . '(check its search, filter, promo-rule and sort settings); the condition matches no products.',
                $attribute
            )
        );
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $this->getSpecialAttributesProvider(),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class),
            $logger
        );
        $condition = $this->getProductConditionMock();
        $condition->method('getValue')->willReturn('1');
        $condition->method('getInputType')->willReturn('string');
        $condition->method('__call')->willReturnMap([
            ['getAttribute', [], $attribute],
            ['getOperator', [], $operator],
        ]);

        for ($i = 0; $i < 2; $i++) {
            $query = $queryBuilder->getSearchQuery($condition);
            $this->assertInstanceOf(Terms::class, $query);
            $this->assertSame('entity_id', $query->getField());
            $this->assertSame([], $query->getValues());
        }
    }

    /**
     * Direct callers can omit the logger without needing a global ObjectManager for warnings.
     *
     * @return void
     */
    public function testUnmappedAttributeWithoutLoggerMatchesNothing()
    {
        $builder = new QueryBuilder(
            $this->getAdvancedAttributeList([new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER)]),
            $this->getQueryFactory(),
            $this->getSpecialAttributesProvider(),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );
        $condition = $this->getProductConditionMock();
        $condition->method('getValue')->willReturn('1');
        $condition->method('__call')->willReturnMap([
            ['getAttribute', [], 'brand'],
            ['getOperator', [], '=='],
        ]);

        $query = $builder->getSearchQuery($condition);
        $this->assertInstanceOf(Terms::class, $query);
        $this->assertSame('entity_id', $query->getField());
        $this->assertSame([], $query->getValues());
    }

    /**
     * @return array
     */
    public static function unmappedAttributeProvider(): array
    {
        $cases = [];
        foreach (['brand', 'sku', 'price', 'stock.qty'] as $attribute) {
            foreach (['==', '!=', '()', '!()', '{}', '!{}', '>=', '<=>'] as $operator) {
                $cases[$attribute . ' ' . $operator] = [$attribute, $operator];
            }
        }

        return $cases;
    }

    /**
     * Attribute availability must use the same field aliases as field lookup.
     *
     * @return void
     */
    public function testAttributeAvailabilityUsesMappedFieldName()
    {
        $attributeList = $this->getAdvancedAttributeList([
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
        ]);

        $this->assertTrue($attributeList->hasField('category_ids'));
        $this->assertTrue($attributeList->hasField('category_id'));
        $this->assertFalse($attributeList->hasField('price'));
    }

    /**
     * Empty values retain the original null or SKU Boolean query behavior.
     *
     * @dataProvider emptyAttributeProvider
     * @param string $attribute Attribute code.
     * @param mixed  $value     Empty value.
     * @param bool   $mapped    Whether the field is mapped.
     * @return void
     */
    public function testEmptyAttributeQuery($attribute, $value, $mapped)
    {
        $fields = [new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER)];
        if ($mapped) {
            $fields[] = new Field($attribute, FieldInterface::FIELD_TYPE_TEXT);
        }
        $attributeList = $this->getAdvancedAttributeList($fields);
        $logger = $this->createMock(LoggerInterface::class);
        if ($attribute === 'sku' && !$mapped) {
            $logger->expects($this->once())->method('warning');
        } else {
            $logger->expects($this->never())->method('warning');
        }
        $builder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $this->getSpecialAttributesProvider(),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class),
            $logger
        );
        $condition = $this->getProductConditionMock();
        $condition->method('getValue')->willReturn($value);
        $condition->method('__call')->willReturnMap([
            ['getAttribute', [], $attribute],
            ['getOperator', [], '=='],
        ]);

        $query = $builder->getSearchQuery($condition);
        if ($attribute !== 'sku') {
            $this->assertNull($query);
        } elseif (!$mapped) {
            $this->assertInstanceOf(Terms::class, $query);
            $this->assertSame([], $query->getValues());
        } else {
            $this->assertInstanceOf(Boolean::class, $query);
            $this->assertSame(1, $query->getMinimumShouldMatch());
            $this->assertCount(1, $query->getShould());
            $match = $query->getShould()[0];
            $this->assertInstanceOf(MatchQuery::class, $match);
            $this->assertSame('sku.untouched', $match->getField());
            $this->assertSame('', $match->getQueryText());
            $this->assertSame('100%', $match->getMinimumShouldMatch());
        }
    }

    /**
     * @return array
     */
    public static function emptyAttributeProvider(): array
    {
        $cases = [];
        foreach (['', [], ['', '']] as $index => $value) {
            foreach ([true, false] as $mapped) {
                $cases['brand empty ' . $index . ' mapped=' . (int) $mapped] = ['brand', $value, $mapped];
            }
        }
        $cases['sku empty string mapped=1'] = ['sku', '', true];
        $cases['sku empty string mapped=0'] = ['sku', '', false];

        return $cases;
    }

    /**
     * A registered SKU special attribute overwrites the already constructed SKU query.
     *
     * @return void
     */
    public function testSkuSpecialAttributeOverridesSkuQuery()
    {
        $queryTypes = [];
        $realFactory = $this->getQueryFactory();
        $factory = $this->createMock(QueryFactory::class);
        $factory->method('create')->willReturnCallback(function ($type, $params) use (&$queryTypes, $realFactory) {
            $queryTypes[] = $type;

            return $realFactory->create($type, $params);
        });
        $specialQuery = new Terms([], 'entity_id');
        $specialAttribute = $this->createMock(HasImage::class);
        $specialAttribute->expects($this->once())->method('getSearchQuery')->willReturnCallback(
            function () use (&$queryTypes, $specialQuery) {
                $this->assertSame([QueryInterface::TYPE_MATCH, QueryInterface::TYPE_BOOL], $queryTypes);

                return $specialQuery;
            }
        );
        $builder = new QueryBuilder(
            $this->getAdvancedAttributeList([
                new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
                new Field('sku', FieldInterface::FIELD_TYPE_TEXT),
            ]),
            $factory,
            new SpecialAttributesProvider(['sku' => $specialAttribute]),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );
        $condition = $this->getProductConditionMock();
        $condition->method('getValue')->willReturn('');
        $condition->method('__call')->willReturnMap([
            ['getAttribute', [], 'sku'],
            ['getOperator', [], '=='],
        ]);

        $this->assertSame($specialQuery, $builder->getSearchQuery($condition));
    }

    /**
     * Field resolvers provided by extensions must participate in availability checks.
     *
     * @return void
     */
    public function testAttributeAvailabilityUsesFieldResolver()
    {
        $attributeList = $this->getMockBuilder(AttributeList::class)
            ->disableOriginalConstructor()->onlyMethods(['getField'])->getMock();
        $attributeList->expects($this->once())->method('getField')->with('extension_field')
            ->willReturn(new Field('extension_field', FieldInterface::FIELD_TYPE_INTEGER));

        $this->assertTrue($attributeList->hasField('extension_field'));
    }

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
        $builder = new QueryBuilder(
            $this->getAdvancedAttributeList([
                new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
                new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            ]),
            $this->getQueryFactory(),
            $this->getSpecialAttributesProvider(),
            [],
            $this->createMock(QueryDependencyRecorderInterface::class),
            $this->createMock(LoggerInterface::class)
        );
        $conditions = [];
        $children = [];
        foreach (['unmapped', 'brand'] as $attribute) {
            $condition = $this->getProductConditionMock();
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
        $property->setValue($combine, $this->getQueryFactory());

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

    /**
     * Test query builder with special attributes for has_image.
     *
     * @return void
     */
    public function testSpecialAttributesHasImageQuery()
    {
        $attributeList = $this->getAttributeList();
        $attributeList->expects($this->never())->method('hasField');
        $attributeList->expects($this->never())->method('getField');
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'has_image'],
        ]);

        $hasImage->expects($this->exactly(1))->method('getSearchQuery')
            ->with($productCondition)->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        $attributeList->expects($this->exactly(0))->method('getField');
        $queryBuilder->getSearchQuery($productCondition);
    }

    /**
     * Test query builder with special attributes for stock.is_in_stock.
     *
     * @return void
     */
    public function testSpecialAttributesIsInStockQuery()
    {
        $attributeList = $this->getAttributeList();
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'stock.is_in_stock'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(1))->method('getSearchQuery')
            ->with($productCondition)->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        $attributeList->expects($this->exactly(0))->method('getField');
        $queryBuilder->getSearchQuery($productCondition);
    }

    /**
     * Test query builder with special attributes for price.is_discount.
     *
     * @return void
     */
    public function testSpecialAttributesIsDiscountQuery()
    {
        $attributeList = $this->getAttributeList();
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'price.is_discount'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(1))->method('getSearchQuery')
            ->with($productCondition)->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        $attributeList->expects($this->exactly(0))->method('getField');
        $queryBuilder->getSearchQuery($productCondition);
    }

    /**
     * Test that the contexts a special attribute depends on are recorded, its search query being still built.
     *
     * @return void
     */
    public function testSpecialAttributesContextDependencies()
    {
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $dependencyRecorder        = $this->createMock(QueryDependencyRecorderInterface::class);
        $queryBuilder = new QueryBuilder(
            $this->getAttributeList(),
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $dependencyRecorder
        );

        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        $isDiscount->method('getContextDependencies')->willReturn(['customer_group']);
        $isDiscount->expects($this->once())->method('getSearchQuery')
            ->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $dependencyRecorder->expects($this->once())->method('addContextDependencies')->with(['customer_group']);

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([['getAttribute', [], 'price.is_discount']]);
        $queryBuilder->getSearchQuery($productCondition);

        // A special attribute not depending on any context records nothing.
        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        $hasImage->method('getSearchQuery')->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([['getAttribute', [], 'has_image']]);
        $queryBuilder->getSearchQuery($productCondition);
    }

    /**
     * Test query builder with special attributes for stock.qty.
     *
     * @return void
     */
    public function testSpecialAttributesStockQtyQuery()
    {
        $attributeList = $this->getAttributeList();
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("1");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'stock.qty'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(1))->method('getSearchQuery')
            ->with($productCondition)->willReturn($this->getMockBuilder(QueryInterface::class)->getMock());
        $attributeList->expects($this->exactly(0))->method('getField');
        $queryBuilder->getSearchQuery($productCondition);
    }

    /**
     * Test query builder with the sku through the special attributes method.
     *
     * @return void
     */
    public function testSpecialAttributesSkuQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('sku', FieldInterface::FIELD_TYPE_TEXT),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("ABC123");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'sku'],
            ['getOperator', [], '()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getType());

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getMust());
        $this->assertEmpty($searchQuery->getMustNot());
        $this->assertCount(1, $searchQuery->getShould());

        $clause = current($searchQuery->getShould());
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(Terms::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $clause->getType());

        /** @var Terms $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals(['ABC123'], $clause->getValues());
    }

    /**
     * Test query builder with several sku values through the "is one of" operator.
     *
     * @return void
     */
    public function testSpecialAttributesMultipleSkuIsOneOfQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('sku', FieldInterface::FIELD_TYPE_TEXT, null, ['is_searchable' => 1, 'default_analyzer' => 'reference']),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("ABC123, DEF456");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'sku'],
            ['getOperator', [], '()'],
        ]);

        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getType());

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getMust());
        $this->assertEmpty($searchQuery->getMustNot());
        $this->assertCount(1, $searchQuery->getShould());

        $clause = current($searchQuery->getShould());
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(Terms::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $clause->getType());

        /** @var Terms $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals(['ABC123', 'DEF456'], $clause->getValues());
    }

    /**
     * Test query builder with the sku through the special attributes method.
     *
     * @return void
     */
    public function testSpecialAttributesMultipleSkuQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('sku', FieldInterface::FIELD_TYPE_TEXT, null, ['is_searchable' => 1, 'default_analyzer' => 'reference']),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("ABC123, DEF456");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'sku'],
            ['getOperator', [], '!()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getType());

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getMust());
        $this->assertCount(1, $searchQuery->getMustNot());
        $this->assertEmpty($searchQuery->getShould());

        $clause = current($searchQuery->getMustNot());
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(Terms::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $clause->getType());

        /** @var Terms $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals(['ABC123', 'DEF456'], $clause->getValues());
    }

    /**
     * Test query builder with the sku through the special attributes method.
     *
     * @return void
     */
    public function testSpecialAttributesMultipleSkuAnalyzedQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('sku', FieldInterface::FIELD_TYPE_TEXT, null, ['is_searchable' => 1, 'default_search_analyzer' => 'reference']),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("ABC123, DEF456");
        $productCondition->method('getInputType')->willReturn("sku");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'sku'],
            ['getOperator', [], '{}'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getType());

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getMust());
        $this->assertEmpty($searchQuery->getMustNot());
        $this->assertCount(2, $searchQuery->getShould());

        $clauses = $searchQuery->getShould();
        $clause = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(MatchQuery::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $clause->getType());

        /** @var MatchQuery $clause */
        $this->assertEquals('sku.reference', $clause->getField());
        $this->assertEquals('DEF456', $clause->getQueryText());
        $this->assertEquals('100%', $clause->getMinimumShouldMatch());

        $clause = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(MatchQuery::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $clause->getType());

        /** @var MatchQuery $clause */
        $this->assertEquals('sku.reference', $clause->getField());
        $this->assertEquals('ABC123', $clause->getQueryText());
        $this->assertEquals('100%', $clause->getMinimumShouldMatch());
    }

    /**
     * Test query builder with a positive match on description.
     *
     * @return void
     */
    public function testTextAttributeMatchQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("white bag");
        $productCondition->method('getInputType')->willReturn("string");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'description'],
            ['getOperator', [], '{}'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(MatchQuery::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $searchQuery->getType());

        /** @var MatchQuery $searchQuery */
        $this->assertEquals('description.standard', $searchQuery->getField());
        $this->assertEquals('white bag', $searchQuery->getQueryText());
        $this->assertEquals('100%', $searchQuery->getMinimumShouldMatch());
    }

    /**
     * Test query builder with a negative match on description.
     *
     * @return void
     */
    public function testTextAttributeNegativeMatchQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('option_text_brand', FieldInterface::FIELD_TYPE_TEXT),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("white bag");
        $productCondition->method('getInputType')->willReturn("string");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'description'],
            ['getOperator', [], '!{}'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Not::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NOT, $searchQuery->getType());

        /** @var Not $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(MatchQuery::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_MATCH, $searchQuery->getQuery()->getType());

        /** @var MatchQuery $matchQuery */
        $matchQuery = $searchQuery->getQuery();
        $this->assertEquals('description.standard', $matchQuery->getField());
        $this->assertEquals('white bag', $matchQuery->getQueryText());
        $this->assertEquals('100%', $matchQuery->getMinimumShouldMatch());
    }


    /**
     * Test query builder with positive contain query.
     *
     * @return void
     */
    public function testSelectAttributePositiveContainQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn("myManufacturer");
        $productCondition->method('getInputType')->willReturn("string");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'brand'],
            ['getOperator', [], '()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Terms::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $searchQuery->getType());

        /** @var Terms $searchQuery */
        $this->assertEquals('brand', $searchQuery->getField());
        $this->assertEquals(['myManufacturer'], $searchQuery->getValues());
    }

    /**
     * Test query builder with multiple negative contain query.
     *
     * @return void
     */
    public function testSelectAttributeNegativeMultipleContainQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(['myManufacturer', ' ', 'myOtherManufacturer ']);
        $productCondition->method('getInputType')->willReturn("string");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'brand'],
            ['getOperator', [], '!()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Not::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NOT, $searchQuery->getType());

        /** @var Not $searchQuery */
        $innerQuery = $searchQuery->getQuery();
        $this->assertInstanceOf(QueryInterface::class, $innerQuery);
        $this->assertInstanceOf(Terms::class, $innerQuery);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $innerQuery->getType());

        /** @var Terms $innerQuery */
        $this->assertEquals('brand', $innerQuery->getField());
        $this->assertEquals(['myManufacturer', ' ', 'myOtherManufacturer '], $innerQuery->getValues());
    }

    /**
     * Test query builder with nested price structure query.
     *
     * @return void
     */
    public function testPriceRangeQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(203.50);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'price'],
            ['getOperator', [], '>='],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Nested::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $searchQuery->getType());

        /** @var Nested $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(Range::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getQuery()->getType());
        $this->assertEquals('price', $searchQuery->getPath());

        $innerQuery = $searchQuery->getQuery();
        /** @var Range $innerQuery */
        $this->assertEquals('price.price', $innerQuery->getField());
        $this->assertEquals(['gte' => 203.50], $innerQuery->getBounds());
    }

    /**
     * Test query builder with nested price structure and nested filter query.
     *
     * @return void
     */
    public function testPriceRangeWithFilterQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryFactory         = $this->getQueryFactory();
        $queryContextResolver = $this->createMock(QueryContextResolverInterface::class);
        $queryContextResolver->method('getValues')->with(['customer_group'])->willReturn(['customer_group' => '2']);
        $dependencyRecorder = $this->createMock(QueryDependencyRecorderInterface::class);
        $dependencyRecorder->expects($this->once())->method('addContextDependencies')->with(['customer_group']);
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $queryFactory,
            $specialAttributesProvider,
            ['price' => new PriceNestedFilter($queryFactory, $queryContextResolver)],
            $dependencyRecorder
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(203.50);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'price'],
            ['getOperator', [], '>='],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Nested::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $searchQuery->getType());

        /** @var Nested $searchQuery */
        $this->assertInstanceOf(Boolean::class, $searchQuery->getQuery());
        $this->assertEquals('price', $searchQuery->getPath());

        // Only the prices of the current customer group are matched.
        /** @var Boolean $innerQuery */
        $innerQuery = $searchQuery->getQuery();
        $this->assertEmpty($innerQuery->getShould());
        $this->assertEmpty($innerQuery->getMustNot());
        [$filterQuery, $rangeQuery] = $innerQuery->getMust();

        /** @var Term $filterQuery */
        $this->assertInstanceOf(Term::class, $filterQuery);
        $this->assertEquals('price.customer_group_id', $filterQuery->getField());
        $this->assertSame(2, $filterQuery->getValue());

        /** @var Range $rangeQuery */
        $this->assertInstanceOf(Range::class, $rangeQuery);
        $this->assertEquals('price.price', $rangeQuery->getField());
        $this->assertEquals(['gte' => 203.50], $rangeQuery->getBounds());
    }

    /**
     * Test query builder with nested price structure and nested filter query.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testNumericRangeWithFilterQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('volume', FieldInterface::FIELD_TYPE_DOUBLE),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        // Greater than or equal.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(37);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'volume'],
            ['getOperator', [], '>='],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());
        /** @var Range $searchQuery */
        $this->assertEquals('volume', $searchQuery->getField());
        $this->assertEquals(['gte' => 37.0], $searchQuery->getBounds());

        // Greater than.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(37);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'volume'],
            ['getOperator', [], '>'],
        ]);
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());
        /** @var Range $searchQuery */
        $this->assertEquals('volume', $searchQuery->getField());
        $this->assertEquals(['gt' => 37.0], $searchQuery->getBounds());

        // Lower than.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(37);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'volume'],
            ['getOperator', [], '<'],
        ]);
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());
        /** @var Range $searchQuery */
        $this->assertEquals('volume', $searchQuery->getField());
        $this->assertEquals(['lt' => 37.0], $searchQuery->getBounds());

        // Lower than or equal.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn(37);
        $productCondition->method('getInputType')->willReturn("numeric");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'volume'],
            ['getOperator', [], '<='],
        ]);
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());
        /** @var Range $searchQuery */
        $this->assertEquals('volume', $searchQuery->getField());
        $this->assertEquals(['lte' => 37.0], $searchQuery->getBounds());
    }

    /**
     * Test query builder with missing brand attribute.
     *
     * @return void
     */
    public function testMissingAttributeQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('volume', FieldInterface::FIELD_TYPE_DOUBLE),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        // Is undefined.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('');
        $productCondition->method('getInputType')->willReturn("select");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'brand'],
            ['getOperator', [], '<=>'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Missing::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_MISSING, $searchQuery->getType());
        /** @var Missing $searchQuery */
        $this->assertEquals('brand', $searchQuery->getField());
    }

    /**
     * Test query builder with a boolean attribute.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBooleanAttributeQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('erin_recommends', FieldInterface::FIELD_TYPE_BOOLEAN),
            new Field('volume', FieldInterface::FIELD_TYPE_DOUBLE),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        // Is true.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('1');
        $productCondition->method('getInputType')->willReturn("select");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'erin_recommends'],
            ['getOperator', [], '=='],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Terms::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $searchQuery->getType());
        /** @var Terms $searchQuery */
        $this->assertEquals('erin_recommends', $searchQuery->getField());
        $this->assertEquals([true], $searchQuery->getValues());

        // Is false.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('0');
        $productCondition->method('getInputType')->willReturn("select");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'erin_recommends'],
            ['getOperator', [], '=='],
        ]);
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Terms::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $searchQuery->getType());
        /** @var Terms $searchQuery */
        $this->assertEquals('erin_recommends', $searchQuery->getField());
        $this->assertEquals([false], $searchQuery->getValues());

        // Is not true.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('1');
        $productCondition->method('getInputType')->willReturn("select");
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'erin_recommends'],
            ['getOperator', [], '!='],
        ]);
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Not::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NOT, $searchQuery->getType());

        /** @var Not $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(Terms::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_TERMS, $searchQuery->getQuery()->getType());

        /** @var Terms $innerQuery */
        $innerQuery = $searchQuery->getQuery();
        $this->assertEquals('erin_recommends', $innerQuery->getField());
        $this->assertEquals([true], $innerQuery->getValues());
    }

    /**
     * Test query builder with category condition.
     *
     * @return void
     */
    public function testCategoryNoFilterQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('erin_recommends', FieldInterface::FIELD_TYPE_BOOLEAN),
            new Field('volume', FieldInterface::FIELD_TYPE_DOUBLE),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
            [],
            $this->createMock(QueryDependencyRecorderInterface::class)
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        // Is true.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('4');
        $productCondition->method('getInputType')->willReturn('category');
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'category_ids'],
            ['getOperator', [], '()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Nested::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $searchQuery->getType());

        /** @var Nested $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(Terms::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_TERMS, $searchQuery->getQuery()->getType());
        $this->assertEquals('category', $searchQuery->getPath());

        /** @var Terms $innerQuery */
        $innerQuery = $searchQuery->getQuery();
        $this->assertEquals('category.category_id', $innerQuery->getField());
        $this->assertEquals(['4'], $innerQuery->getValues());
    }

    /**
     * Test query builder with category condition and nested filter.
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCategoryWithNestedFilterQuery()
    {
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('category.category_id', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('category.category_uid', FieldInterface::FIELD_TYPE_TEXT, 'category'),
            new Field('category.position', FieldInterface::FIELD_TYPE_INTEGER, 'category'),
            new Field('price.price', FieldInterface::FIELD_TYPE_DOUBLE, 'price'),
            new Field('erin_recommends', FieldInterface::FIELD_TYPE_BOOLEAN),
            new Field('volume', FieldInterface::FIELD_TYPE_DOUBLE),
            new Field(
                'sku',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'reference']
            ),
            new Field(
                'description',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
            new Field('brand', FieldInterface::FIELD_TYPE_INTEGER),
            new Field(
                'option_text_brand',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => 1, 'default_search_analyzer' => 'standard']
            ),
        ];

        $queryFactory = $this->getQueryFactory();

        $nestedFilterQuery = $queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['value' => 'ABCDEF', 'field' => 'category.uid', 'name' => 'NestedFilterQuery']
        );
        $nestedFilterMock = $this->getMockbuilder(Product\NestedFilterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nestedFilterMock->method('getFilter')->willReturn($nestedFilterQuery);

        // A nested filter not depending on any context records nothing.
        $dependencyRecorder = $this->createMock(QueryDependencyRecorderInterface::class);
        $dependencyRecorder->expects($this->never())->method('addContextDependencies');

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $queryFactory,
            $specialAttributesProvider,
            ['category' => $nestedFilterMock],
            $dependencyRecorder
        );

        /** @var MockObject $hasImage */
        $hasImage = $specialAttributesProvider->getAttribute('has_image');
        /** @var MockObject $isInStock */
        $isInStock = $specialAttributesProvider->getAttribute('stock.is_in_stock');
        /** @var MockObject $isDiscount */
        $isDiscount = $specialAttributesProvider->getAttribute('price.is_discount');
        /** @var MockObject $stockQty */
        $stockQty = $specialAttributesProvider->getAttribute('stock.qty');

        // Is true.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn('4');
        $productCondition->method('getInputType')->willReturn('category');
        $productCondition->method('__call')->willReturnMap([
            ['getAttribute', [], 'category_ids'],
            ['getOperator', [], '()'],
        ]);

        $hasImage->expects($this->exactly(0))->method('getSearchQuery');
        $isInStock->expects($this->exactly(0))->method('getSearchQuery');
        $isDiscount->expects($this->exactly(0))->method('getSearchQuery');
        $stockQty->expects($this->exactly(0))->method('getSearchQuery');
        // $attributeList->expects($this->exactly(1))->method('getField');
        $searchQuery = $queryBuilder->getSearchQuery($productCondition);

        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Nested::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NESTED, $searchQuery->getType());

        /** @var Nested $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(Boolean::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getQuery()->getType());
        $this->assertEquals('category', $searchQuery->getPath());

        /** @var Boolean $innerQuery */
        $innerQuery = $searchQuery->getQuery();
        $this->assertCount(2, $innerQuery->getMust());
        $this->assertEmpty($innerQuery->getShould());
        $this->assertEmpty($innerQuery->getMustNot());

        $clauses = $innerQuery->getMust();

        $originalQuery = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $originalQuery);
        $this->assertInstanceOf(Terms::class, $originalQuery);
        $this->assertEquals(QueryInterface::TYPE_TERMS, $originalQuery->getType());
        $this->assertEquals('category.category_id', $originalQuery->getField());
        $this->assertEquals(['4'], $originalQuery->getValues());

        $filterQuery = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $filterQuery);
        $this->assertEquals('NestedFilterQuery', $filterQuery->getName());
    }

    /**
     * Mock the query factory used by the builder.
     *
     * @return QueryFactory
     */
    protected function getQueryFactory()
    {
        $factories = [];

        foreach ($this->queryTypes as $currentType => $queryClass) {
            $queryCreateCallback = function ($queryParams) use ($queryClass) {
                return new $queryClass(...$queryParams);
            };
            $factory = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();

            $factory->method('create')->willReturnCallback($queryCreateCallback);

            $factories[$currentType] = $factory;
        }

        return new QueryFactory($factories);
    }

    /**
     * Get special attributes provider.
     *
     * @return SpecialAttributesProvider
     */
    protected function getSpecialAttributesProvider()
    {
        $specialAttributes = [
            'has_image'         => $this->getMockBuilder(HasImage::class)->disableOriginalConstructor()->getMock(),
            'stock.is_in_stock' => $this->getMockBuilder(IsInStock::class)->disableOriginalConstructor()->getMock(),
            'price.is_discount' => $this->getMockBuilder(IsDiscount::class)->disableOriginalConstructor()->getMock(),
            'stock.qty'         => $this->getMockBuilder(StockQty::class)->disableOriginalConstructor()->getMock(),
        ];

        return new SpecialAttributesProvider($specialAttributes);
    }

    /**
     * Get attribute list.
     *
     * @return AttributeList|MockObject
     */
    protected function getAttributeList()
    {
        $attributeList = $this->getMockBuilder(AttributeList::class)->disableOriginalConstructor()->getMock();
        $attributeList->method('getField')->willReturnMap([
            ['category_ids', 'category.category_id'],
            ['category_id', 'category.category_id'],
            ['category_uid', 'category.category_uid'],
            ['position', 'category.position'],
            ['price', 'price.price'],
            ['sku', 'sku'],
            ['brand', 'brand'],
        ]);

        return $attributeList;
    }

    /**
     * Get partially mocked attribute list.
     *
     * @param array $mappingFields Mapping fields.
     * @param array $mockMethods   Methods to mock.
     *
     * @return AttributeList|MockObject
     */
    protected function getAdvancedAttributeList($mappingFields, $mockMethods = ['getAttributeCollection'])
    {
        $mapping = new Mapping('entity_id', $mappingFields);

        $defaultStore = $this->getMockBuilder(StoreInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager = $this->getMockbuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $storeManager->method('getDefaultStoreView')->willReturn($defaultStore);
        $index = $this->getMockbuilder(IndexInterface::class)->disableOriginalConstructor()->getMock();
        $index->method('getMapping')->willReturn($mapping);
        $indexManager = $this->getMockbuilder(IndexOperationInterface::class)->disableOriginalConstructor()->getMock();
        $indexManager->method('getIndexByName')->willReturn($index);

        $fieldMapper = new Mapper();

        $category = $this->getMockbuilder(AbstractAttribute::class)->disableOriginalConstructor()->getMock();
        $category->method('__call')->willReturnMap([
            ['getFrontendLabel', [], 'Category'],
        ]);
        $category->method('getAttributeCode')->willReturn('category_ids');

        $price = $this->getMockbuilder(AbstractAttribute::class)->disableOriginalConstructor()->getMock();
        $price->method('__call')->willReturnMap([
            ['getFrontendLabel', [], 'Price'],
        ]);
        $category->method('getAttributeCode')->willReturn('price');

        $sku = $this->getMockbuilder(AbstractAttribute::class)->disableOriginalConstructor()->getMock();
        $sku->method('__call')->willReturnMap([
            ['getFrontendLabel', [], 'Sku'],
        ]);
        $sku->method('getAttributeCode')->willReturn('sku');

        $brand = $this->getMockbuilder(AbstractAttribute::class)->disableOriginalConstructor()->getMock();
        $brand->method('__call')->willReturnMap([
            ['getFrontendLabel', [], 'Brand'],
            ['isFilterable', [], true],
        ]);
        $brand->method('getAttributeCode')->willReturn('brand');

        $layeredNavAttrProvider = $this->getMockbuilder(LayeredNavAttributesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layeredNavAttrProvider->method('getList')->willReturn([]);

        $attributeCollectionFactory = $this->getMockbuilder(CollectionFactory::class)->disableOriginalConstructor()->getMock();

        $attributeListMock = $this->getMockbuilder(AttributeList::class)
            ->setConstructorArgs([
                $attributeCollectionFactory,
                $storeManager,
                $indexManager,
                $this->getMockbuilder(MappingHelper::class)->disableOriginalConstructor()->getMock(),
                $fieldMapper,
                $layeredNavAttrProvider,
            ])
            ->onlyMethods($mockMethods)->getMock();
        $attributeListMock->method('getAttributeCollection')->willReturn([
            $category,
            $price,
            $sku,
            $brand,
        ]);

        return $attributeListMock;
    }

    /**
     * Get Product Condition mock object.
     *
     * @return Product|MockObject
     */
    protected function getProductConditionMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }
}
