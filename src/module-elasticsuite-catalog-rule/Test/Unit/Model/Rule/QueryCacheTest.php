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
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Unit test for {@see QueryCache}.
 *
 * Covered scenarios:
 * - Entries are read back from the shared cache by another process (in-process cache empty)
 * - The in-process only mode never touches the shared cache
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
     * @var array Shared cache storage: cache key => ['data' => serialized entry, 'tags' => tags].
     */
    private $storage = [];

    /**
     * Test that data is stored in the shared cache with its tags, and read back by another process.
     *
     * @return void
     */
    public function testSharedEntry(): void
    {
        $this->createQueryCache()->save('key', 'query', ['ES_VCAT_3']);

        $this->assertSame(['key'], array_keys($this->storage));
        $this->assertSame(['ES_VCAT_3'], $this->storage['key']['tags']);
        $cacheData = $this->createQueryCache()->load('key');
        $this->assertSame('query', $cacheData['data']);
        $this->assertSame(['ES_VCAT_3'], $cacheData['tags']);
        $this->assertFalse($this->createQueryCache()->load('other key'));
    }

    /**
     * Test that the in-process only mode does not use the shared cache.
     *
     * @return void
     */
    public function testInProcessOnly(): void
    {
        $queryCache = $this->createQueryCache();
        $queryCache->save('key', 'draft query', ['ES_VCAT_3'], false);

        $this->assertSame([], $this->storage);
        $this->assertSame('draft query', $queryCache->load('key', false)['data']);
        $this->assertFalse($this->createQueryCache()->load('key'), 'Another process does not see it.');
    }

    /**
     * Test that reset forgets the in-process entries, the shared ones being still available.
     *
     * @return void
     */
    public function testReset(): void
    {
        $queryCache = $this->createQueryCache();
        $queryCache->save('draft', 'draft query', ['ES_VCAT_3'], false);
        $queryCache->save('key', 'query', ['ES_VCAT_3']);
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
        $this->storage['key'] = ['data' => serialize(['data' => 'query']), 'tags' => []];

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
            function ($data, $cacheKey, $tags) {
                $this->storage[$cacheKey] = ['data' => $data, 'tags' => $tags];

                return true;
            }
        );

        return new QueryCache($sharedCache);
    }
}
