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

use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test for {@see CacheCleaner}.
 *
 * Covered scenarios:
 * - The shared cache entries depending on the categories are cleaned, and the in-process layers are reset
 * - Nothing happens when there is no category to clean
 * - Within a transaction, cleaning is deferred until the commit
 * - The ancestors of the categories are cleaned as well when requested
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CacheCleanerTest extends TestCase
{
    /**
     * Test cleaning the entries depending on some categories.
     *
     * @return void
     */
    public function testClean(): void
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['ES_VCAT_2', 'ES_VCAT_3']);
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->once())->method('reset');
        $rootCategoryProvider = $this->createMock(RootCategoryProvider::class);
        $rootCategoryProvider->expects($this->once())->method('reset');

        $this->createCleaner($sharedCache, $queryCache, $rootCategoryProvider)->clean([2, 3]);
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

        $this->createCleaner($sharedCache, $queryCache, $rootCategoryProvider)->clean([]);
    }

    /**
     * Test that, within a transaction, the entries are only cleaned once it is committed.
     *
     * @return void
     */
    public function testCleanAfterCommitWithinTransaction(): void
    {
        $commitCallbacks  = [];
        $categoryResource = $this->createCategoryResource(1);
        $categoryResource->method('addCommitCallback')->willReturnCallback(
            function ($callback) use (&$commitCallbacks, $categoryResource) {
                $commitCallbacks[] = $callback;

                return $categoryResource;
            }
        );
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['ES_VCAT_2', 'ES_VCAT_3']);
        $cleaner = $this->createCleaner(
            $sharedCache,
            $this->createMock(QueryCache::class),
            $this->createMock(RootCategoryProvider::class),
            $categoryResource
        );

        $cleaner->cleanAfterCommit([2, 3]);
        $this->assertCount(1, $commitCallbacks, 'Cleaning is deferred until the commit.');

        call_user_func($commitCallbacks[0]);
    }

    /**
     * Test that, outside of any transaction, the entries are cleaned right away.
     *
     * @return void
     */
    public function testCleanAfterCommitWithoutTransaction(): void
    {
        $categoryResource = $this->createCategoryResource(0);
        $categoryResource->expects($this->never())->method('addCommitCallback');
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['ES_VCAT_2', 'ES_VCAT_3']);

        $this->createCleaner(
            $sharedCache,
            $this->createMock(QueryCache::class),
            $this->createMock(RootCategoryProvider::class),
            $categoryResource
        )->cleanAfterCommit([2, 3]);
    }

    /**
     * Test that the entries depending on the categories or on any of their ancestors are cleaned.
     *
     * @return void
     */
    public function testCleanWithAncestors(): void
    {
        $categoryResource = $this->createCategoryResource(0, ['1/2/3/12', '1/2/3/34']);
        $sharedCache      = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->expects($this->once())->method('clean')
            ->with(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                ['ES_VCAT_1', 'ES_VCAT_2', 'ES_VCAT_3', 'ES_VCAT_12', 'ES_VCAT_34']
            );

        $this->createCleaner(
            $sharedCache,
            $this->createMock(QueryCache::class),
            $this->createMock(RootCategoryProvider::class),
            $categoryResource
        )->cleanWithAncestors([12, 34]);
    }

    /**
     * Create the cache cleaner.
     *
     * @param ElasticsuiteCache     $sharedCache          Shared cache mock.
     * @param QueryCache            $queryCache           Query cache mock.
     * @param RootCategoryProvider  $rootCategoryProvider Root category provider mock.
     * @param CategoryResource|null $categoryResource     Category resource mock.
     *
     * @return CacheCleaner
     */
    private function createCleaner(
        $sharedCache,
        $queryCache,
        $rootCategoryProvider,
        $categoryResource = null
    ): CacheCleaner {
        return new CacheCleaner(
            $sharedCache,
            new DependencyCollector(),
            $categoryResource ?: $this->createMock(CategoryResource::class),
            $queryCache,
            $rootCategoryProvider
        );
    }

    /**
     * Create a category resource whose connection is at the given transaction level, and returns the given paths.
     *
     * @param int      $transactionLevel Transaction level of the connection.
     * @param string[] $paths            Category paths returned by the connection.
     *
     * @return CategoryResource|MockObject
     */
    private function createCategoryResource(int $transactionLevel, array $paths = []): CategoryResource
    {
        $select = $this->createMock(Select::class);
        $select->method('distinct')->willReturnSelf();
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('getTransactionLevel')->willReturn($transactionLevel);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchCol')->with($select)->willReturn($paths);

        $categoryResource = $this->createMock(CategoryResource::class);
        $categoryResource->method('getConnection')->willReturn($connection);
        $categoryResource->method('getEntityTable')->willReturn('catalog_category_entity');

        return $categoryResource;
    }
}
