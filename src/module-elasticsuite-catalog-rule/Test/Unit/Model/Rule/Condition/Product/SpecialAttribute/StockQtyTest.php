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

use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\StockQty;

/**
 * Stock Qty special attribute unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class StockQtyTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $stockQty = new StockQty();

        $this->assertEquals('stock.qty', $stockQty->getAttributeCode());
        $this->assertNull($stockQty->getOperatorName());
        $this->assertEquals('numeric', $stockQty->getInputType());
        $this->assertEquals('text', $stockQty->getValueElementType());

        $this->assertEquals('...', $stockQty->getValueName(null));
        $this->assertEquals('...', $stockQty->getValueName(''));
        $this->assertEquals('randomValue', $stockQty->getValueName('randomValue'));
        $this->assertEquals('randomValue', $stockQty->getValue('randomValue'));

        $this->assertIsArray($stockQty->getValueOptions());
        $this->assertEmpty($stockQty->getValueOptions());

        $this->assertEquals(__('Stock qty'), $stockQty->getLabel());
    }

    /**
     * Test search query building.
     *
     * @return void
     */
    public function testGetSearchQuery()
    {
        $stockQty = new StockQty();

        $this->assertNull($stockQty->getSearchQuery($this->getProductConditionMock()));
    }
}
