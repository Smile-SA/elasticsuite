<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request;

use Smile\ElasticsuiteCore\Search\Request\SortOrder\Script as ScriptSortOrder;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Script sort order unit testing.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class ScriptTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test basic script order.
     *
     * @return void
     */
    public function testBasicScriptSortOrder()
    {
        $scriptType = 'number';
        $scriptLang = 'painless';
        $scriptSource = "doc['sortField'].value * params.factor";

        $scriptSorOrder = new ScriptSortOrder($scriptType, $scriptLang, $scriptSource);

        // Default values.
        $this->assertNull($scriptSorOrder->getName());
        $this->assertEquals(ScriptSortOrder::SCRIPT_FIELD, $scriptSorOrder->getField());
        $this->assertEquals(SortOrderInterface::SORT_ASC, $scriptSorOrder->getDirection());
        $this->assertEquals(ScriptSortOrder::TYPE_SCRIPT, $scriptSorOrder->getType());
        $this->assertEquals(SortOrderInterface::MISSING_LAST, $scriptSorOrder->getMissing());

        $this->assertEquals(
            [
                'lang'   => $scriptLang,
                'source' => $scriptSource,
                'params' => [],
            ],
            $scriptSorOrder->getScript(),
        );
        $this->assertEquals($scriptType, $scriptSorOrder->getScriptType());
    }

    /**
     * Test script order with all params provided.
     *
     * @return void
     */
    public function testAdvancedScriptSortOrder()
    {
        $scriptType = 'number';
        $scriptLang = 'painless';
        $scriptSource = "doc['sortField'].value * params.factor";
        $scriptParams = ['factor' => 1.1];
        $scriptDirection = SortOrderInterface::SORT_DESC;
        $scriptName = 'mySortOrder';
        $scriptMissing = SortOrderInterface::MISSING_FIRST;

        $scriptSorOrder = new ScriptSortOrder(
            $scriptType,
            $scriptLang,
            $scriptSource,
            $scriptParams,
            $scriptDirection,
            $scriptName,
            $scriptMissing
        );

        // Default values.
        $this->assertEquals($scriptName, $scriptSorOrder->getName());
        $this->assertEquals(ScriptSortOrder::SCRIPT_FIELD, $scriptSorOrder->getField());
        $this->assertEquals(SortOrderInterface::SORT_DESC, $scriptSorOrder->getDirection());
        $this->assertEquals(ScriptSortOrder::TYPE_SCRIPT, $scriptSorOrder->getType());
        $this->assertEquals(SortOrderInterface::MISSING_FIRST, $scriptSorOrder->getMissing());

        $this->assertEquals(
            [
                'lang'   => $scriptLang,
                'source' => $scriptSource,
                'params' => $scriptParams,
            ],
            $scriptSorOrder->getScript(),
        );
        $this->assertEquals($scriptType, $scriptSorOrder->getScriptType());
    }
}
