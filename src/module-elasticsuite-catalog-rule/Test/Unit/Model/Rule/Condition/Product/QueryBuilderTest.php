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
use Smile\ElasticsuiteCatalog\Model\Attribute\LayeredNavAttributesProvider;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList;
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
     * Test query builder with special attributes for has_image.
     *
     * @return void
     */
    public function testSpecialAttributesHasImageQuery()
    {
        $attributeList = $this->getAttributeList();
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
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
        $this->assertInstanceOf(MatchQuery::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $clause->getType());

        /** @var MatchQuery $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals('ABC123', $clause->getQueryText());
        $this->assertEquals('100%', $clause->getMinimumShouldMatch());
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
        $this->assertCount(2, $searchQuery->getMustNot());
        $this->assertEmpty($searchQuery->getShould());

        $clauses = $searchQuery->getMustNot();
        $clause = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(MatchQuery::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $clause->getType());

        /** @var MatchQuery $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals('DEF456', $clause->getQueryText());
        $this->assertEquals('100%', $clause->getMinimumShouldMatch());

        $clause = array_pop($clauses);
        $this->assertInstanceOf(QueryInterface::class, $clause);
        $this->assertInstanceOf(MatchQuery::class, $clause);
        $this->assertEquals(QueryInterface::TYPE_MATCH, $clause->getType());

        /** @var MatchQuery $clause */
        $this->assertEquals('sku.untouched', $clause->getField());
        $this->assertEquals('ABC123', $clause->getQueryText());
        $this->assertEquals('100%', $clause->getMinimumShouldMatch());
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
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $this->getQueryFactory(),
            $specialAttributesProvider,
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

        $attributeList = $this->getAdvancedAttributeList($fields);
        $specialAttributesProvider = $this->getSpecialAttributesProvider();
        $queryBuilder = new QueryBuilder(
            $attributeList,
            $queryFactory,
            $specialAttributesProvider,
            ['category' => $nestedFilterMock],
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
     *
     * @return AttributeList|MockObject
     */
    protected function getAdvancedAttributeList($mappingFields)
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
            ->onlyMethods(['getAttributeCollection'])->getMock();
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
