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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\Collection;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test for {@see CacheCleaner}.
 *
 * Covered scenarios:
 * - The shared cache entries of the categories are cleaned, and the in-process layers are reset
 * - The virtual categories using a cleaned category as virtual root are cleaned with their ancestors, recursively
 * - Nothing happens when there is no category to clean
 * - The ancestors of the categories are cleaned as well when requested
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CacheCleanerTest extends TestCase
{
    /**
     * Test cleaning the entries of some categories.
     *
     * @return void
     */
    public function testClean(): void
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['cat_c_2', 'cat_c_3']);
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->once())->method('reset');
        $rootCategoryProvider = $this->createMock(RootCategoryProvider::class);
        $rootCategoryProvider->expects($this->once())->method('reset');

        $this->createCleaner($sharedCache, $queryCache, null, [], $rootCategoryProvider)->clean([2, 3]);
    }

    /**
     * Test that the virtual categories using a cleaned category as virtual root are cleaned with their ancestors,
     * and the ones using them as root as well: 31 (path 1/2/30/31) uses 12 as root, 41 (path 1/2/40/41) uses 31.
     *
     * @return void
     */
    public function testCleanVirtualRootUsers(): void
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                ['cat_c_1', 'cat_c_2', 'cat_c_12', 'cat_c_30', 'cat_c_31', 'cat_c_40', 'cat_c_41']
            );

        $rootUsers = [[$this->getCategory('1/2/30/31')], [$this->getCategory('1/2/40/41')], []];
        $this->createCleaner($sharedCache, $this->createMock(QueryCache::class), null, $rootUsers)->clean([1, 2, 12]);
    }

    /**
     * Test that nothing happens without categories.
     *
     * @return void
     */
    public function testCleanNothing(): void
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->never())->method('clean');
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->never())->method('reset');
        $rootCategoryProvider = $this->createMock(RootCategoryProvider::class);
        $rootCategoryProvider->expects($this->never())->method('reset');

        $this->createCleaner($sharedCache, $queryCache, null, [], $rootCategoryProvider)->clean([]);
    }

    /**
     * Test that the entries of the categories and of any of their ancestors are cleaned.
     *
     * @return void
     */
    public function testCleanWithAncestors(): void
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                ['cat_c_1', 'cat_c_2', 'cat_c_3', 'cat_c_12', 'cat_c_34']
            );

        $this->createCleaner(
            $sharedCache,
            $this->createMock(QueryCache::class),
            $this->createCategoryResource(['1/2/3/12', '1/2/3/34'])
        )->cleanWithAncestors([12, 34]);
    }

    /**
     * Create the cache cleaner.
     *
     * @param ElasticsuiteCache         $sharedCache          Shared cache mock.
     * @param QueryCache                $queryCache           Query cache mock.
     * @param CategoryResource|null     $categoryResource     Category resource mock.
     * @param array                     $rootUsers            Virtual categories found by each successive lookup of
     *                                                        the virtual categories using a cleaned category as root.
     * @param RootCategoryProvider|null $rootCategoryProvider Root category provider mock.
     *
     * @return CacheCleaner
     */
    private function createCleaner(
        $sharedCache,
        $queryCache,
        $categoryResource = null,
        array $rootUsers = [],
        $rootCategoryProvider = null
    ): CacheCleaner {
        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturnCallback(
            function () use (&$rootUsers) {
                $collection = $this->createMock(Collection::class);
                $collection->method('addAttributeToFilter')->willReturnSelf();
                $collection->method('getIterator')->willReturn(new \ArrayIterator(array_shift($rootUsers) ?? []));

                return $collection;
            }
        );

        return new CacheCleaner(
            $sharedCache,
            $queryCache,
            $categoryResource ?: $this->createMock(CategoryResource::class),
            $collectionFactory,
            $rootCategoryProvider ?: $this->createMock(RootCategoryProvider::class)
        );
    }

    /**
     * Create a category resource whose connection returns the given paths.
     *
     * @param string[] $paths Category paths returned by the connection.
     *
     * @return CategoryResource|MockObject
     */
    private function createCategoryResource(array $paths): CategoryResource
    {
        $select = $this->createMock(Select::class);
        $select->method('distinct')->willReturnSelf();
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->with($select)->willReturn($paths);

        $categoryResource = $this->createMock(CategoryResource::class);
        $categoryResource->method('getConnection')->willReturn($connection);
        $categoryResource->method('getEntityTable')->willReturn('catalog_category_entity');

        return $categoryResource;
    }

    /**
     * Category with the given path.
     *
     * @param string $path Category path.
     *
     * @return Category
     */
    private function getCategory(string $path): Category
    {
        $category = $this->getMockBuilder(Category::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $category->setData('path', $path);

        return $category;
    }
}
