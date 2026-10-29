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

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test for {@see CacheCleaner::clean()}.
 *
 * Covered scenarios:
 * - The shared cache entries of the categories are cleaned, and the in-process layers are reset
 * - Nothing happens when there is no category to clean
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

        (new CacheCleaner($sharedCache, $queryCache, $rootCategoryProvider))->clean([2, 3]);
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

        (new CacheCleaner($sharedCache, $queryCache, $rootCategoryProvider))->clean([]);
    }
}
