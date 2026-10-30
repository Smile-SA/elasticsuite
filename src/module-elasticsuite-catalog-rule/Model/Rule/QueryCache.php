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

namespace Smile\ElasticsuiteCatalogRule\Model\Rule;

use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Two-level (in-process and shared) cache of the data built from rules (eg. virtual category search queries).
 *
 * Each entry holds the data along with its cache tags, expiration date and the contexts it depends on.
 * Data depending on some contexts (eg. the customer group, for a "is discount" condition) is stored per value of these
 * contexts, the entry stored under the context-less cache key being then a marker redirecting to it. Any other data is
 * shared by all contexts.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryCache
{
    /**
     * @var ElasticsuiteCache
     */
    private $sharedCache;

    /**
     * @var QueryContextResolverInterface
     */
    private $queryContextResolver;

    /**
     * @var array[]
     */
    private $localCache = [];

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache             $sharedCache          Elasticsuite cache.
     * @param QueryContextResolverInterface $queryContextResolver Values of the contexts rule queries can depend on.
     */
    public function __construct(ElasticsuiteCache $sharedCache, QueryContextResolverInterface $queryContextResolver)
    {
        $this->sharedCache          = $sharedCache;
        $this->queryContextResolver = $queryContextResolver;
    }

    /**
     * Load an entry for the current values of the contexts it depends on.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string $cacheKey       Cache key, not including any context.
     * @param bool   $useSharedCache Whether to look into the shared cache, and not only into the in-process one.
     *
     * @return array|false Entry (data, tags, expiresAt, context) or false if missing.
     */
    public function load(string $cacheKey, bool $useSharedCache = true)
    {
        $cacheData = $this->get($cacheKey, $useSharedCache);

        if ($cacheData !== false && !empty($cacheData['context'])) {
            $cacheData = $this->get($this->getContextCacheKey($cacheKey, $cacheData['context']), $useSharedCache);
        }

        return $cacheData;
    }

    /**
     * Save an entry, for the current values of the contexts the data depends on, if any.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string   $cacheKey            Cache key, not including any context.
     * @param mixed    $data                Data.
     * @param string[] $cacheTags           Cache tags.
     * @param int|null $expiresAt           Expiration date as a Unix timestamp. Null if the data does not expire.
     * @param string[] $contextDependencies Codes of the contexts the data depends on.
     * @param bool     $useSharedCache      Whether to save into the shared cache, and not only into the in-process one.
     *
     * @return void
     */
    public function save(
        string $cacheKey,
        $data,
        array $cacheTags,
        ?int $expiresAt,
        array $contextDependencies,
        bool $useSharedCache = true
    ): void {
        sort($contextDependencies);
        $cacheData = [
            'data'      => $data,
            'tags'      => $cacheTags,
            'expiresAt' => $expiresAt,
            'context'   => $contextDependencies,
        ];

        if (!empty($contextDependencies)) {
            // The marker shares the data tags and expiration: it is invalidated whenever the data could be.
            $this->set($cacheKey, ['data' => null] + $cacheData, $useSharedCache);
            $cacheKey = $this->getContextCacheKey($cacheKey, $contextDependencies);
        }

        $this->set($cacheKey, $cacheData, $useSharedCache);
    }

    /**
     * Forget the in-process cache entries (eg. once some of the shared cache entries have been cleaned).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->localCache = [];
    }

    /**
     * Get an entry from the in-process cache, or from the shared cache.
     *
     * @param string $cacheKey       Cache key.
     * @param bool   $useSharedCache Whether to look into the shared cache.
     *
     * @return array|false
     */
    private function get(string $cacheKey, bool $useSharedCache)
    {
        $cacheData = $this->localCache[$cacheKey] ?? false;

        if ($cacheData === false && $useSharedCache) {
            $cacheData = $this->sharedCache->load($cacheKey);
            // Due to the fact we serialize/unserialize completely pre-built queries as object.
            // We cannot use any implementation of SerializerInterface.
            $cacheData = $cacheData ? unserialize($cacheData) : false;
            // Entries not matching the expected format (eg. saved by a previous version) are considered as missing.
            $cacheData = $this->isValid($cacheData) ? $cacheData : false;

            if ($cacheData !== false) {
                $this->localCache[$cacheKey] = $cacheData;
            }
        }

        // Long-running processes (eg. queue consumers) may outlive the entry expiration date.
        if ($cacheData !== false && $cacheData['expiresAt'] !== null && $cacheData['expiresAt'] <= time()) {
            unset($this->localCache[$cacheKey]);
            $cacheData = false;
        }

        return $cacheData;
    }

    /**
     * Save an entry into the in-process cache, and into the shared cache.
     *
     * @param string $cacheKey       Cache key.
     * @param array  $cacheData      Entry.
     * @param bool   $useSharedCache Whether to save into the shared cache.
     *
     * @return void
     */
    private function set(string $cacheKey, array $cacheData, bool $useSharedCache): void
    {
        $this->localCache[$cacheKey] = $cacheData;

        if ($useSharedCache) {
            $lifetime = ($cacheData['expiresAt'] === null) ? null : max(1, $cacheData['expiresAt'] - time());
            $this->sharedCache->save(serialize($cacheData), $cacheKey, $cacheData['tags'], $lifetime);
        }
    }

    /**
     * Whether an unserialized entry has the expected format.
     *
     * @param mixed $cacheData Unserialized entry.
     *
     * @return bool
     */
    private function isValid($cacheData): bool
    {
        return is_array($cacheData)
            && array_key_exists('data', $cacheData)
            && isset($cacheData['tags'], $cacheData['context'])
            && array_key_exists('expiresAt', $cacheData);
    }

    /**
     * Cache key of an entry for the current values of the contexts it depends on (eg. "<key>|customer_group=1").
     *
     * @param string   $cacheKey Cache key, not including any context.
     * @param string[] $codes    Codes of the contexts the entry depends on, sorted.
     *
     * @return string
     */
    private function getContextCacheKey(string $cacheKey, array $codes): string
    {
        foreach ($this->queryContextResolver->getValues($codes) as $code => $value) {
            $cacheKey .= '|' . $code . '=' . $value;
        }

        return $cacheKey;
    }
}
