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
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsInStock;

/**
 * IsInStock special attribute rule unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class IsInStockTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $booleanSource = new Yesno();
        $isInStock = new IsInStock($booleanSource);

        $this->assertEquals('stock.is_in_stock', $isInStock->getAttributeCode());
        $this->assertEquals(' ', $isInStock->getOperatorName());
        $this->assertEquals('select', $isInStock->getInputType());
        $this->assertEquals('hidden', $isInStock->getValueElementType());
        $this->assertEquals(' ', $isInStock->getValueName('randomValue'));
        $this->assertEquals(true, $isInStock->getValue('randomValue'));
        $this->assertEquals($booleanSource->toOptionArray(), $isInStock->getValueOptions());
        $this->assertEquals(__('Only in stock products'), $isInStock->getLabel());
    }

    /**
     * Test search query building.
     *
     * @return void
     */
    public function testGetSearchQuery()
    {
        $booleanSource = new Yesno();
        $isInStock = new IsInStock($booleanSource);

        $this->assertNull($isInStock->getSearchQuery($this->getProductConditionMock()));
    }
}
