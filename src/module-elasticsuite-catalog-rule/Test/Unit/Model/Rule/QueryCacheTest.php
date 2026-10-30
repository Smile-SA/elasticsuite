<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Unit test for {@see QueryCache}.
 *
 * Covered scenarios:
 * - Context independent data is shared by all contexts
 * - Context dependent data is stored per value of the contexts it depends on, behind a marker
 * - Entries are read back from the shared cache by another process (in-process cache empty)
 * - The in-process only mode never touches the shared cache
 * - Expiration date is turned into a cache lifetime, and expired entries are ignored
 * - Entries not matching the expected format are considered as missing
 * - Reset forgets the in-process entries
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryCacheTest extends TestCase
{
    /**
     * @var array Shared cache storage: cache key => ['data' => serialized entry, 'tags' => tags, 'lifetime' => lifetime].
     */
    private $storage = [];

    /**
     * @var string[] Current context values, by code.
     */
    private $context = ['customer_group' => '0', 'retailer' => '12'];

    /**
     * Test that context independent data is saved once and served to all contexts.
     *
     * @return void
     */
    public function testContextIndependentData(): void
    {
        $this->createQueryCache()->save('key', 'query', ['ES_VCAT_3'], null, []);

        $this->assertSame(['key'], array_keys($this->storage));
        $this->assertSame(['ES_VCAT_3'], $this->storage['key']['tags']);
        $this->assertNull($this->storage['key']['lifetime']);

        foreach (['0', '1', '2'] as $customerGroupId) {
            $this->context['customer_group'] = $customerGroupId;
            $cacheData = $this->createQueryCache()->load('key');
            $this->assertSame('query', $cacheData['data']);
            $this->assertSame([], $cacheData['context']);
        }
    }

    /**
     * Test that context dependent data is stored per context value, behind a marker sharing its tags.
     *
     * @return void
     */
    public function testContextDependentData(): void
    {
        $this->context['customer_group'] = '1';
        $this->createQueryCache()->save('key', 'query for group 1', ['ES_VCAT_51'], null, ['customer_group']);

        $this->assertSame(['key', 'key|customer_group=1'], array_keys($this->storage));
        $this->assertSame(['ES_VCAT_51'], $this->storage['key']['tags'], 'The marker is invalidated with the data.');

        $cacheData = $this->createQueryCache()->load('key');
        $this->assertSame('query for group 1', $cacheData['data']);
        $this->assertSame(['customer_group'], $cacheData['context']);

        $this->context['retailer'] = '31';
        $this->assertNotFalse($this->createQueryCache()->load('key'), 'Other contexts are not taken into account.');

        $this->context['customer_group'] = '2';
        $this->assertFalse($this->createQueryCache()->load('key'), 'Another group has its own entry.');
    }

    /**
     * Test that data depending on several contexts is stored per combination of their values, sorted by code.
     *
     * @return void
     */
    public function testSeveralContexts(): void
    {
        $this->createQueryCache()->save('key', 'query', ['ES_VCAT_51'], null, ['retailer', 'customer_group']);

        $this->assertSame(['key', 'key|customer_group=0|retailer=12'], array_keys($this->storage));
        $this->assertSame('query', $this->createQueryCache()->load('key')['data']);

        $this->context['retailer'] = '31';
        $this->assertFalse($this->createQueryCache()->load('key'), 'Another retailer has its own entry.');
    }

    /**
     * Test that the in-process only mode does not use the shared cache.
     *
     * @return void
     */
    public function testInProcessOnly(): void
    {
        $queryCache = $this->createQueryCache();
        $queryCache->save('key', 'draft query', ['ES_VCAT_3'], null, [], false);

        $this->assertSame([], $this->storage);
        $this->assertSame('draft query', $queryCache->load('key', false)['data']);
        $this->assertFalse($this->createQueryCache()->load('key'), 'Another process does not see it.');
    }

    /**
     * Test the expiration date handling.
     *
     * @return void
     */
    public function testExpiration(): void
    {
        $this->createQueryCache()->save('key', 'query', ['ES_VCAT_3'], time() + 3600, []);
        $this->assertGreaterThanOrEqual(3599, $this->storage['key']['lifetime']);
        $this->assertLessThanOrEqual(3600, $this->storage['key']['lifetime']);

        $queryCache = $this->createQueryCache();
        $queryCache->save('expired', 'query', ['ES_VCAT_3'], time() - 10, []);
        $this->assertSame(1, $this->storage['expired']['lifetime'], 'A null or 0 lifetime would mean no expiration.');
        $this->assertFalse($queryCache->load('expired'), 'Expired entries are ignored, even in-process.');
    }

    /**
     * Test that reset forgets the in-process entries, the shared ones being still available.
     *
     * @return void
     */
    public function testReset(): void
    {
        $queryCache = $this->createQueryCache();
        $queryCache->save('draft', 'draft query', ['ES_VCAT_3'], null, [], false);
        $queryCache->save('key', 'query', ['ES_VCAT_3'], null, []);
        $queryCache->reset();

        $this->assertFalse($queryCache->load('draft', false), 'In-process entries are forgotten.');
        $this->assertSame('query', $queryCache->load('key')['data'], 'Shared entries are loaded again.');
    }

    /**
     * Test that entries not matching the expected format are considered as missing.
     *
     * @return void
     */
    public function testInvalidEntry(): void
    {
        $this->storage['key'] = ['data' => serialize(['query' => 'query', 'tags' => []]), 'tags' => [], 'lifetime' => null];

        $this->assertFalse($this->createQueryCache()->load('key'));
    }

    /**
     * Create a query cache, as a new process would, sharing the cache storage of the test.
     *
     * @return QueryCache
     */
    private function createQueryCache(): QueryCache
    {
        $sharedCache = $this->createMock(ElasticsuiteCache::class);
        $sharedCache->method('load')->willReturnCallback(
            function ($cacheKey) {
                return $this->storage[$cacheKey]['data'] ?? false;
            }
        );
        $sharedCache->method('save')->willReturnCallback(
            function ($data, $cacheKey, $tags, $lifetime) {
                $this->storage[$cacheKey] = ['data' => $data, 'tags' => $tags, 'lifetime' => $lifetime];

                return true;
            }
        );

        $queryContextResolver = $this->createMock(QueryContextResolverInterface::class);
        $queryContextResolver->method('getValues')->willReturnCallback(
            function (array $codes) {
                return array_intersect_key($this->context, array_flip($codes));
            }
        );

        return new QueryCache($sharedCache, $queryContextResolver);
    }
}
