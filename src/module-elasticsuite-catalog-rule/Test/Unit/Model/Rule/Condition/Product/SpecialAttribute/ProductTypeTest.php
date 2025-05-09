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

use Magento\Config\Model\Config\Source\Yesno;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\ProductType;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\StockQty;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Term;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product Type special attribute rule unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class ProductTypeTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $booleanSource = new Yesno();
        $attributeCode = 'attributeCode';
        $typeIdentifier = 'typeIdentifier';
        $attributeLabel = 'attributeLabel';

        $productType = new ProductType(
            $this->getQueryFactory(),
            $booleanSource,
            $attributeCode,
            $typeIdentifier,
            $attributeLabel
        );

        $this->assertEquals($attributeCode, $productType->getAttributeCode());
        $this->assertEquals(' ', $productType->getOperatorName());

        $this->assertEquals('select', $productType->getInputType());
        $this->assertEquals('hidden', $productType->getValueElementType());

        $this->assertEquals(' ', $productType->getValueName(null));
        $this->assertEquals(' ', $productType->getValueName(''));
        $this->assertEquals(' ', $productType->getValueName('randomValue'));

        $this->assertEquals(true, $productType->getValue(null));
        $this->assertEquals(true, $productType->getValue(''));
        $this->assertEquals(true, $productType->getValue('randomValue'));

        $this->assertEquals($booleanSource->toOptionArray(), $productType->getValueOptions());

        $this->assertEquals(__($attributeLabel), $productType->getLabel());
    }

    /**
     * Test search query building.
     *
     * @return void
     */
    public function testGetSearchQuery()
    {
        $booleanSource = new Yesno();
        $attributeCode = 'attributeCode';
        $typeIdentifier = 'typeIdentifier';
        $attributeLabel = 'attributeLabel';

        $productType = new ProductType(
            $this->getQueryFactory(),
            $booleanSource,
            $attributeCode,
            $typeIdentifier,
            $attributeLabel
        );

        $searchQuery = $productType->getSearchQuery($this->getProductConditionMock());
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getShould());
        $this->assertEmpty($searchQuery->getMustNot());
        $this->assertCount(1, $searchQuery->getMust());

        $mustClause = current($searchQuery->getMust());
        $this->assertInstanceOf(Terms::class, $mustClause);
        /** @var Terms $mustClause */

        $this->assertEquals('type_id', $mustClause->getField());
        $this->assertEquals([$typeIdentifier], $mustClause->getValues());
    }
}
