<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\ItemDataFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Preview;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;

/**
 * Unit test for the sort orders of the category {@see Preview}.
 *
 * Covered scenarios:
 * - The "position" sort order of a category is restricted to the positions of this category, as on the storefront
 * - No restriction is applied to a category not saved yet
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AllowMockObjectsWithoutExpectations]
class PreviewTest extends TestCase
{
    /**
     * Test the "position" sort order of the preview of a category is restricted to its positions.
     *
     * @return void
     */
    public function testPositionSortIsRestrictedToTheCategory(): void
    {
        $calls = $this->prepareProductCollection(11);

        $sortFilter = ['category.category_id' => 11];

        $this->assertSame(
            [
                ['addSortFilterParameters', ['position', 'category.position', 'category', $sortFilter]],
                ['setOrder', ['position', Collection::SORT_ORDER_DESC]],
            ],
            $calls
        );
    }

    /**
     * Test the "position" sort order of the preview of a category not saved yet is not restricted.
     *
     * @return void
     */
    public function testPositionSortOfNewCategoryIsNotRestricted(): void
    {
        $calls = $this->prepareProductCollection(null);

        $this->assertSame([['setOrder', ['position', Collection::SORT_ORDER_DESC]]], $calls);
    }

    /**
     * Prepare the preview product collection of a standard category sorted by position, and return the sort related
     * calls received by the collection, in order.
     *
     * @param int|null $categoryId Category id.
     *
     * @return array
     */
    private function prepareProductCollection(?int $categoryId): array
    {
        $calls      = [];
        $collection = $this->createMock(Collection::class);
        $collection->method('addSortFilterParameters')->willReturnCallback(
            function (...$args) use (&$calls, $collection) {
                $calls[] = ['addSortFilterParameters', $args];

                return $collection;
            }
        );
        $collection->method('setOrder')->willReturnCallback(
            function (...$args) use (&$calls, $collection) {
                $calls[] = ['setOrder', $args];

                return $collection;
            }
        );

        $method = new \ReflectionMethod(Preview::class, 'prepareProductCollection');
        $method->invoke($this->getPreview($categoryId), $collection);

        return $calls;
    }

    /**
     * Build the preview of a standard category, sorted by position.
     *
     * @param int|null $categoryId Category id.
     *
     * @return Preview
     */
    private function getPreview(?int $categoryId): Preview
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn(1);

        $virtualRule = $this->createMock(Rule::class);
        $virtualRule->method('getCategorySearchQuery')->willReturn($this->createMock(QueryInterface::class));

        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getStore'])
            ->getMock();
        $category->method('getId')->willReturn($categoryId);
        $category->method('getStore')->willReturn($store);
        $category->setData(['store_id' => 1, 'is_virtual_category' => false, 'virtual_rule' => $virtualRule]);

        $queryFactory = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturn($this->createMock(QueryInterface::class));

        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            function ($param, $default = null) {
                return $param === 'default_sort_by' ? 'position' : $default;
            }
        );

        $categoryConfig = $this->createMock(Config::class);
        $categoryConfig->method('getProductListDefaultSortBy')->willReturn('position');

        return new Preview(
            $category,
            $this->getMockBuilder(CollectionFactory::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(ItemDataFactory::class),
            $queryFactory,
            $this->createMock(ContextInterface::class),
            10,
            '',
            $request,
            $categoryConfig
        );
    }
}
