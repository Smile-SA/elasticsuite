<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\VirtualCategory;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\Collection;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root;

/**
 * Root test.
 *
 * Covers a disabled category anywhere in a virtual category's subtree path
 * (e.g. a disabled parent category) must not prevent rebuilding that path's url.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class RootTest extends TestCase
{
    /**
     * CollectionFactory is a Magento-generated class, absent from autoloading in a plain unit test run.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(CollectionFactory::class, false)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            require_once dirname(__DIR__, 9) . '/generated/code/Smile/ElasticsuiteVirtualCategory/Model/'
                . 'ResourceModel/VirtualCategory/CollectionFactory.php';
        }
    }

    /**
     * A disabled category anywhere in the subtree path must still contribute its url_key: it is excluded from
     * the active-only admin category listing, but not from the url actually browsed on the storefront.
     *
     * @return void
     */
    public function testGetVirtualCategorySubtreePathWithDisabledCategoryInPath(): void
    {
        $categories = [
            21 => $this->createCategory('tops'),
            44 => $this->createCategory('disabled-parent'),
            45 => $this->createCategory('virtual-category'),
        ];

        $root = $this->createRoot([21, 44, 45], $categories);

        $path = $root->getVirtualCategorySubtreePath(
            $this->createMock(CategoryInterface::class),
            $this->createMock(CategoryInterface::class)
        );

        $this->assertSame('tops/disabled-parent/virtual-category', $path);
    }

    /**
     * A category id that genuinely cannot be found (e.g. deleted) is skipped rather than crashing the whole
     * storefront page, even though the url it produces is then incomplete.
     *
     * @return void
     */
    public function testGetVirtualCategorySubtreePathWithMissingCategorySkipsIt(): void
    {
        $categories = [21 => $this->createCategory('tops'), 45 => $this->createCategory('virtual-category')];

        // 999 is absent from $categories entirely, not just disabled.
        $root = $this->createRoot([21, 999, 45], $categories);

        $path = $root->getVirtualCategorySubtreePath(
            $this->createMock(CategoryInterface::class),
            $this->createMock(CategoryInterface::class)
        );

        $this->assertSame('tops/virtual-category', $path);
    }

    /**
     * Create a category mock with a fixed url_key.
     *
     * @param string $urlKey Url key.
     *
     * @return Category
     */
    private function createCategory(string $urlKey): Category
    {
        $category = $this->getMockBuilder(Category::class)->disableOriginalConstructor()
            ->onlyMethods(['getUrlKey'])->getMock();
        $category->method('getUrlKey')->willReturn($urlKey);

        return $category;
    }

    /**
     * Create a Root instance whose getSubtreePathIds() returns the given ids, and whose category collection
     * factory returns the given categories (keyed by id) without ever filtering on active status.
     *
     * @param int[]               $subtreePathIds Ids returned by getSubtreePathIds().
     * @param CategoryInterface[] $categories     Categories returned by the collection, keyed by id.
     *
     * @return Root
     */
    private function createRoot(array $subtreePathIds, array $categories): Root
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->never())->method('addIsActiveFilter');
        $collection->method('addUrlRewriteToResult')->willReturnSelf();
        $collection->method('addNameToResult')->willReturnSelf();
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addIdFilter')->willReturnSelf();
        $collection->method('getItems')->willReturn($categories);

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $root = $this->getMockBuilder(Root::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSubtreePathIds'])
            ->getMock();
        $root->method('getSubtreePathIds')->willReturn($subtreePathIds);

        $property = new \ReflectionProperty(Root::class, 'categoryCollectionFactory');
        $property->setAccessible(true);
        $property->setValue($root, $collectionFactory);

        return $root;
    }
}
