<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Observer;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer as OptimizerResource;
use Smile\ElasticsuiteCatalogOptimizer\Observer\CleanFunctionsCache;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Unit test for {@see CleanFunctionsCache}.
 *
 * Covered scenarios:
 * - Cleaning the cache of an optimizer cleans the optimizer functions from the Elasticsuite cache type
 * - Within a transaction (optimizer save or deletion), cleaning is deferred until the commit
 * - Cleaning the cache of any other object does nothing
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CleanFunctionsCacheTest extends TestCase
{
    /**
     * Test that cleaning the cache of an optimizer cleans the optimizer functions.
     *
     * @return void
     */
    public function testOptimizer(): void
    {
        $cache = $this->createMock(ElasticsuiteCache::class);
        $cache->expects($this->once())->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, [Optimizer::CACHE_TAG]);
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->once())->method('reset');

        (new CleanFunctionsCache($cache, $queryCache))->execute($this->getObserver($this->createMock(Optimizer::class)));
    }

    /**
     * Test that, within a transaction, the optimizer functions are only cleaned once it is committed.
     *
     * @return void
     */
    public function testOptimizerWithinTransaction(): void
    {
        $commitCallbacks = [];
        $connection      = $this->createMock(AdapterInterface::class);
        $connection->method('getTransactionLevel')->willReturn(1);
        $resource = $this->createMock(OptimizerResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('addCommitCallback')->willReturnCallback(
            function ($callback) use (&$commitCallbacks, $resource) {
                $commitCallbacks[] = $callback;

                return $resource;
            }
        );
        $optimizer = $this->createMock(Optimizer::class);
        $optimizer->method('getResource')->willReturn($resource);

        $cleaned = 0;
        $cache   = $this->createMock(ElasticsuiteCache::class);
        $cache->method('clean')->willReturnCallback(
            function () use (&$cleaned) {
                $cleaned++;

                return true;
            }
        );

        (new CleanFunctionsCache($cache, $this->createMock(QueryCache::class)))->execute($this->getObserver($optimizer));
        $this->assertSame(0, $cleaned, 'Cleaning is deferred until the commit.');
        $this->assertCount(1, $commitCallbacks);

        call_user_func($commitCallbacks[0]);
        $this->assertSame(1, $cleaned, 'Cleaned once committed.');
    }

    /**
     * Test that cleaning the cache of any other object does nothing.
     *
     * @return void
     */
    public function testOtherObject(): void
    {
        $cache = $this->createMock(ElasticsuiteCache::class);
        $cache->expects($this->never())->method('clean');
        $queryCache = $this->createMock(QueryCache::class);
        $queryCache->expects($this->never())->method('reset');

        (new CleanFunctionsCache($cache, $queryCache))->execute($this->getObserver(new DataObject()));
    }

    /**
     * Observer of a "clean_cache_by_tags" event.
     *
     * @param object $object Object whose cache is cleaned.
     *
     * @return Observer
     */
    private function getObserver($object): Observer
    {
        return new Observer(['event' => new Event(['object' => $object])]);
    }
}
