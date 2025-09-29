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
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Adapter\Elasticsuite\Request\SortOrder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder\Builder as SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\Standard as StandardSortOrder;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\Nested as NestedSortOrder;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\Script as ScriptSortOrder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Search adapter sort order builder test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test building simple sort order.
     *
     * @return void
     */
    public function testSimpleSortOrder()
    {
        $requestSortOrders = [
            new StandardSortOrder('field'),
        ];

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders($requestSortOrders);

        $this->assertCount(1, $sortOrders);

        $currentSortOrder = current($sortOrders);

        $this->assertArrayHasKey('field', $currentSortOrder);
        $this->assertEquals(StandardSortOrder::SORT_ASC, $currentSortOrder['field']['order']);
        $this->assertEquals('_last', $currentSortOrder['field']['missing']);
        $this->assertEquals(FieldInterface::FIELD_TYPE_KEYWORD, $currentSortOrder['field']['unmapped_type']);
    }

    /**
     * Test building simple sort order (desc direction).
     *
     * @return void
     */
    public function testSortOrderDescendingSort()
    {
        $requestSortOrders = [
            new StandardSortOrder('field', StandardSortOrder::SORT_DESC),
        ];

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders($requestSortOrders);

        $currentSortOrder = current($sortOrders);

        $this->assertEquals(StandardSortOrder::SORT_DESC, $currentSortOrder['field']['order']);
        $this->assertEquals('_first', $currentSortOrder['field']['missing']);
    }

    /**
     * Test building multiple sort order.
     *
     * @return void
     */
    public function testMultipleSortOrder()
    {
        $requestSortOrders = [
            new StandardSortOrder('field1'),
            new StandardSortOrder('field2'),
        ];

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders($requestSortOrders);

        $this->assertCount(2, $sortOrders);
        $this->assertArrayHasKey('field1', $sortOrders[0]);
        $this->assertArrayHasKey('field2', $sortOrders[1]);
    }

    /**
     * Test building a nested filter sort order.
     *
     * @return void
     */
    public function testNestedSortOrder()
    {
        $requestSortOrders = [
            new NestedSortOrder('parent.child', StandardSortOrder::SORT_ASC, 'parent'),
        ];

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders($requestSortOrders);

        $currentSortOrder = current($sortOrders);

        $this->assertArrayHasKey('parent.child', $currentSortOrder);
        $this->assertEquals(StandardSortOrder::SORT_ASC, $currentSortOrder['parent.child']['order']);
        $this->assertEquals('_last', $currentSortOrder['parent.child']['missing']);
        $this->assertEquals(FieldInterface::FIELD_TYPE_KEYWORD, $currentSortOrder['parent.child']['unmapped_type']);
        $this->assertEquals('parent', $currentSortOrder['parent.child']['nested']['path']);
        $this->assertEquals(NestedSortOrder::SCORE_MODE_MIN, $currentSortOrder['parent.child']['mode']);
    }

    /**
     * Test building a nested filter sort order.
     *
     * @return void
     */
    public function testNestedFilterSortOrder()
    {
        $filterQuery       = $this->getMockBuilder(QueryInterface::class)->getMock();
        $requestSortOrders = [
            new NestedSortOrder('parent.child', StandardSortOrder::SORT_ASC, 'parent', $filterQuery),
        ];

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders($requestSortOrders);

        $currentSortOrder = current($sortOrders);

        $this->assertArrayHasKey('parent.child', $currentSortOrder);
        $this->assertEquals(StandardSortOrder::SORT_ASC, $currentSortOrder['parent.child']['order']);
        $this->assertEquals('_last', $currentSortOrder['parent.child']['missing']);
        $this->assertEquals(FieldInterface::FIELD_TYPE_KEYWORD, $currentSortOrder['parent.child']['unmapped_type']);
        $this->assertEquals('parent', $currentSortOrder['parent.child']['nested']['path']);
        $this->assertEquals(NestedSortOrder::SCORE_MODE_MIN, $currentSortOrder['parent.child']['mode']);
        $this->assertEquals('query', $currentSortOrder['parent.child']['nested']['filter']);
    }

    /**
     * Test building a script sort order (without support for direction yet).
     *
     * @return void
     */
    public function testScriptSortOrder()
    {
        $scriptType = 'number';
        $scriptLang = 'painless';
        $scriptSource = "doc['sortField'].value * params.factor";
        $scriptParams = ['factor' => 1.1];
        $scriptSorOrder = new ScriptSortOrder(
            $scriptType,
            $scriptLang,
            $scriptSource,
            $scriptParams
        );
        $this->assertEquals(ScriptSortOrder::SCRIPT_FIELD, $scriptSorOrder->getField());
        $this->assertEquals(
            [
                'lang' => $scriptLang,
                'source' => $scriptSource,
                'params' => $scriptParams,
            ],
            $scriptSorOrder->getScript()
        );

        $sortOrders = $this->getSortOrderBuilder()->buildSortOrders([$scriptSorOrder]);

        $currentSortOrder = current($sortOrders);
        $this->assertIsArray($currentSortOrder);
        $this->assertArrayHasKey($scriptSorOrder->getField(), $currentSortOrder);
        $this->assertArrayHasKey('type', $currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]);
        $this->assertEquals($scriptSorOrder->getScriptType(), $currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]['type']);
        $this->assertArrayHasKey('script', $currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]);
        $this->assertIsArray($currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]['script']);
        $this->assertEquals(
            $scriptSorOrder->getScript(),
            $currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]['script']
        );
        // 'order' not supported yet.
        $this->assertArrayNotHasKey('order', $currentSortOrder[ScriptSortOrder::SCRIPT_FIELD]);
    }

    /**
     * Init the sort order builder used in tests.
     *
     * @return \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder\Builder
     */
    private function getSortOrderBuilder()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->getMock();
        $queryBuilder->method('buildQuery')->will($this->returnValue('query'));

        return new SortOrderBuilder($queryBuilder);
    }
}
