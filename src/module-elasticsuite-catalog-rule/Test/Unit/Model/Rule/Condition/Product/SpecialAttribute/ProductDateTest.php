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

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule\Condition\Product\SpecialAttribute;

use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\ProductDate;
use Smile\ElasticsuiteCore\Search\Request\Query\Not;
use Smile\ElasticsuiteCore\Search\Request\Query\Range;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * ProductDate special attribute rule unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class ProductDateTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $attributeCode = 'attributeCode';
        $attributeLabel = 'attributeLabel';
        $fieldName = 'fieldName';

        $productDate = new ProductDate(
            $attributeCode,
            $attributeLabel,
            $fieldName,
            $this->getQueryFactory()
        );

        $this->assertEquals($attributeCode, $productDate->getAttributeCode());
        $this->assertNull($productDate->getOperatorName());

        $this->assertEquals('numeric', $productDate->getInputType());
        $this->assertEquals('text', $productDate->getValueElementType());

        $this->assertEquals('...', $productDate->getValueName(null));
        $this->assertEquals('...', $productDate->getValueName(''));
        $this->assertEquals('randomValue', $productDate->getValueName('randomValue'));

        $this->assertEquals(null, $productDate->getValue(null));
        $this->assertEquals('', $productDate->getValue(''));
        $this->assertEquals('randomValue', $productDate->getValue('randomValue'));

        $this->assertIsArray($productDate->getValueOptions());
        $this->assertEmpty($productDate->getValueOptions());

        $this->assertEquals(__($attributeLabel), $productDate->getLabel());
    }

    /**
     * Invalid operator unit test.
     *
     * @return void
     */
    public function testSearchQueryInvalidOperator()
    {
        $attributeCode = 'attributeCode';
        $attributeLabel = 'attributeLabel';
        $fieldName = 'fieldName';

        $productDate = new ProductDate(
            $attributeCode,
            $attributeLabel,
            $fieldName,
            $this->getQueryFactory()
        );

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('__call')->with('getOperator', [])->willReturn('invalidOperator');
        $productCondition->method('getValue')->willReturn(3);

        $this->expectExceptionMessage('Invalid operator');
        $this->expectException(\InvalidArgumentException::class);
        $productDate->getSearchQuery($productCondition);
    }

    /**
     * Test search query with valid equality operators.
     *
     * @return void
     */
    public function testSearchQueryValidEqualityOperators()
    {
        $attributeCode = 'attributeCode';
        $attributeLabel = 'attributeLabel';
        $fieldName = 'fieldName';

        $productDate = new ProductDate(
            $attributeCode,
            $attributeLabel,
            $fieldName,
            $this->getQueryFactory()
        );

        $daysAgo = 3;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        // Positive equality.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('==');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());

        /** @var Range $searchQuery */
        $this->assertEquals($fieldName, $searchQuery->getField());
        $this->assertIsArray($searchQuery->getBounds());
        $this->assertCount(2, $searchQuery->getBounds());
        $this->assertArrayHasKey('gte', $searchQuery->getBounds());
        $this->assertArrayHasKey('lt', $searchQuery->getBounds());
        $bounds = $searchQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['gte']);
        $this->assertEquals($referenceDate->modify('+1 day')->format('Y-m-d'), $bounds['lt']);

        // Negative equality.
        $daysAgo = 4;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('!=');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Not::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_NOT, $searchQuery->getType());

        /** @var Not $searchQuery */
        $this->assertInstanceOf(QueryInterface::class, $searchQuery->getQuery());
        $this->assertInstanceOf(Range::class, $searchQuery->getQuery());
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getQuery()->getType());

        /** @var Range $rangeQuery */
        $rangeQuery = $searchQuery->getQuery();
        $this->assertEquals($fieldName, $rangeQuery->getField());
        $this->assertIsArray($rangeQuery->getBounds());
        $this->assertCount(2, $rangeQuery->getBounds());
        $this->assertArrayHasKey('gte', $rangeQuery->getBounds());
        $this->assertArrayHasKey('lt', $rangeQuery->getBounds());
        $bounds = $rangeQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['gte']);
        $this->assertEquals($referenceDate->modify('+1 day')->format('Y-m-d'), $bounds['lt']);
    }

    /**
     * Test search query with valid greater than operators.
     *
     * @return void
     */
    public function testSearchQueryValidGreaterThanOperators()
    {
        $attributeCode = 'attributeCode';
        $attributeLabel = 'attributeLabel';
        $fieldName = 'fieldName';

        $productDate = new ProductDate(
            $attributeCode,
            $attributeLabel,
            $fieldName,
            $this->getQueryFactory()
        );

        $daysAgo = 3;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        // Greater than.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('>');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());

        /** @var Range $searchQuery */
        $this->assertEquals($fieldName, $searchQuery->getField());
        $this->assertIsArray($searchQuery->getBounds());
        $this->assertCount(1, $searchQuery->getBounds());
        $this->assertArrayHasKey('lt', $searchQuery->getBounds());
        $bounds = $searchQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['lt']);

        // Greater than or equal.
        $daysAgo = 4;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('>=');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());

        /** @var Range $searchQuery */
        $this->assertEquals($fieldName, $searchQuery->getField());
        $this->assertIsArray($searchQuery->getBounds());
        $this->assertCount(1, $searchQuery->getBounds());
        $this->assertArrayHasKey('lte', $searchQuery->getBounds());
        $bounds = $searchQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['lte']);
    }

    /**
     * Test search query with valid lower than operators.
     *
     * @return void
     */
    public function testSearchQueryValidLowerThanOperators()
    {
        $attributeCode = 'attributeCode';
        $attributeLabel = 'attributeLabel';
        $fieldName = 'fieldName';

        $productDate = new ProductDate(
            $attributeCode,
            $attributeLabel,
            $fieldName,
            $this->getQueryFactory()
        );

        $daysAgo = 3;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        // Greater than.
        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('<');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());

        /** @var Range $searchQuery */
        $this->assertEquals($fieldName, $searchQuery->getField());
        $this->assertIsArray($searchQuery->getBounds());
        $this->assertCount(1, $searchQuery->getBounds());
        $this->assertArrayHasKey('gt', $searchQuery->getBounds());
        $bounds = $searchQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['gt']);

        // Greater than or equal.
        $daysAgo = 4;
        $referenceDate = new \DateTime();
        $referenceDate->setTime(0, 0, 0); // Set the time to the start of the day.
        $referenceDate->modify('-' . $daysAgo . ' days');

        $productCondition = $this->getProductConditionMock();
        $productCondition->method('getValue')->willReturn($daysAgo);
        $productCondition->method('__call')->with('getOperator', [])->willReturn('<=');

        $searchQuery = $productDate->getSearchQuery($productCondition);
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Range::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_RANGE, $searchQuery->getType());

        /** @var Range $searchQuery */
        $this->assertEquals($fieldName, $searchQuery->getField());
        $this->assertIsArray($searchQuery->getBounds());
        $this->assertCount(1, $searchQuery->getBounds());
        $this->assertArrayHasKey('gte', $searchQuery->getBounds());
        $bounds = $searchQuery->getBounds();
        $this->assertEquals($referenceDate->format('Y-m-d'), $bounds['gte']);
    }
}
