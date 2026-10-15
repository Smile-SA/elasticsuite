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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;
use Smile\ElasticsuiteVirtualCategory\Plugin\CleanRuleCacheAfterSave;

/**
 * Unit test for {@see CleanRuleCacheAfterSave}.
 *
 * Covered scenarios:
 * - Saving a category without any change of its virtual data cleans nothing
 * - Saving a category with a change of its virtual data (including its enabled state) cleans its path
 * - Deleting a category cleans its path and its descendants, retrieved before they are deleted
 * - Moving a category cleans its former path, its descendants and its new parent path
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CleanRuleCacheAfterSaveTest extends TestCase
{
    /**
     * Virtual data of the saved category before the save.
     */
    private const ORIGINAL_DATA = [
        'is_active'             => '1',
        'is_virtual_category'   => '1',
        'virtual_rule'          => '{"type":"combine"}',
        'virtual_category_root' => '0',
    ];

    /**
     * Test that saving a category without any change of its virtual data cleans nothing.
     *
     * @return void
     */
    public function testSaveWithoutVirtualDataChange(): void
    {
        $cacheCleaner = $this->createMock(CacheCleaner::class);
        $cacheCleaner->expects($this->never())->method('cleanAfterCommit');
        $category = $this->getCategory('1/2/3/12', self::ORIGINAL_DATA, ['name' => 'Renamed'] + self::ORIGINAL_DATA);

        $resource = $this->createMock(CategoryResource::class);
        $this->assertSame($resource, $this->getPlugin($cacheCleaner)->afterSave($resource, $resource, $category));
    }

    /**
     * Test that saving a category with a change of its virtual data cleans the entries depending on its path.
     *
     * @dataProvider virtualDataChangeDataProvider
     *
     * @param array $newData Virtual data of the saved category after the save.
     *
     * @return void
     */
    public function testSaveWithVirtualDataChange(array $newData): void
    {
        $cacheCleaner = $this->createMock(CacheCleaner::class);
        $cacheCleaner->expects($this->once())->method('cleanAfterCommit')->with([1, 2, 3, 12]);
        $category = $this->getCategory('1/2/3/12', self::ORIGINAL_DATA, $newData);

        $resource = $this->createMock(CategoryResource::class);
        $this->getPlugin($cacheCleaner)->afterSave($resource, $resource, $category);
    }

    /**
     * Data provider for {@see testSaveWithVirtualDataChange()}.
     *
     * @return array
     */
    public function virtualDataChangeDataProvider(): array
    {
        return [
            'rule changed'                  => [['virtual_rule' => '{"type":"other"}'] + self::ORIGINAL_DATA],
            'root changed'                  => [['virtual_category_root' => '3'] + self::ORIGINAL_DATA],
            'made standard'                 => [['is_virtual_category' => '0'] + self::ORIGINAL_DATA],
            'disabled'                      => [['is_active' => '0'] + self::ORIGINAL_DATA],
            'values swapped between fields' => [
                ['is_virtual_category' => '0', 'virtual_category_root' => '1'] + self::ORIGINAL_DATA,
            ],
        ];
    }

    /**
     * Test that deleting a category cleans the entries depending on its path or on its descendants, which are
     * retrieved before being deleted along with it.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $isDeleted = false;
        $category  = $this->getCategory('1/2/3/12', self::ORIGINAL_DATA, self::ORIGINAL_DATA);
        $category->method('getAllChildren')->with(true)->willReturnCallback(
            function () use (&$isDeleted) {
                $this->assertFalse($isDeleted, 'Descendants are retrieved before the deletion.');

                return [13, 14];
            }
        );
        $cacheCleaner = $this->createMock(CacheCleaner::class);
        $cacheCleaner->expects($this->once())->method('cleanAfterCommit')->with([1, 2, 3, 12, 13, 14]);

        $resource = $this->createMock(CategoryResource::class);
        $result   = $this->getPlugin($cacheCleaner)->aroundDelete(
            $resource,
            function () use (&$isDeleted, $resource) {
                $isDeleted = true;

                return $resource;
            },
            $category
        );

        $this->assertTrue($isDeleted);
        $this->assertSame($resource, $result);
    }

    /**
     * Test that moving a category cleans the entries depending on its former path, on its descendants or on its new
     * parent path.
     *
     * @return void
     */
    public function testMove(): void
    {
        $category = $this->getCategory('1/2/3/12', self::ORIGINAL_DATA, self::ORIGINAL_DATA);
        $category->method('getAllChildren')->with(true)->willReturn([13]);
        $newParent = $this->getCategory('1/2/40', self::ORIGINAL_DATA, self::ORIGINAL_DATA);
        $cacheCleaner = $this->createMock(CacheCleaner::class);
        $cacheCleaner->expects($this->once())->method('cleanAfterCommit')->with([1, 2, 3, 12, 13, 1, 2, 40]);

        $resource = $this->createMock(CategoryResource::class);
        $this->getPlugin($cacheCleaner)->aroundChangeParent(
            $resource,
            function ($movedCategory) use ($resource) {
                // The paths of the moved category and of its descendants are updated by the move.
                $movedCategory->setData('path', '1/2/40/12');

                return $resource;
            },
            $category,
            $newParent
        );
    }

    /**
     * Plugin under test.
     *
     * @param CacheCleaner $cacheCleaner Cache cleaner mock.
     *
     * @return CleanRuleCacheAfterSave
     */
    private function getPlugin(CacheCleaner $cacheCleaner): CleanRuleCacheAfterSave
    {
        return new CleanRuleCacheAfterSave(
            $this->createMock(CacheInterface::class),
            $this->createMock(CollectionFactory::class),
            $cacheCleaner
        );
    }

    /**
     * Category with the given path, original data and current data.
     *
     * @param string $path         Category path.
     * @param array  $originalData Data loaded from the database.
     * @param array  $data         Current data.
     *
     * @return Category|MockObject
     */
    private function getCategory(string $path, array $originalData, array $data): Category
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllChildren'])
            ->getMock();
        foreach ($originalData as $key => $value) {
            $category->setOrigData($key, $value);
        }
        $category->setData(['path' => $path] + $data);

        return $category;
    }
}
