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

use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Two-level (in-process and shared) cache of the data built from rules (eg. virtual category search queries).
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
     * @var array[]
     */
    private $localCache = [];

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache $sharedCache Elasticsuite cache.
     */
    public function __construct(ElasticsuiteCache $sharedCache)
    {
        $this->sharedCache = $sharedCache;
    }

    /**
     * Load an entry.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string $cacheKey       Cache key.
     * @param bool   $useSharedCache Whether to look into the shared cache, and not only into the in-process one.
     *
     * @return array|false Entry (data) or false if missing.
     */
    public function load(string $cacheKey, bool $useSharedCache = true)
    {
        return $this->get($cacheKey, $useSharedCache);
    }

    /**
     * Save an entry.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string   $cacheKey       Cache key.
     * @param mixed    $data           Data.
     * @param string[] $cacheTags      Cache tags.
     * @param bool     $useSharedCache Whether to save into the shared cache, and not only into the in-process one.
     *
     * @return void
     */
    public function save(string $cacheKey, $data, array $cacheTags, bool $useSharedCache = true): void
    {
        $this->set($cacheKey, ['data' => $data], $cacheTags, $useSharedCache);
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

        return $cacheData;
    }

    /**
     * Save an entry into the in-process cache, and into the shared cache.
     *
     * @param string   $cacheKey       Cache key.
     * @param array    $cacheData      Entry.
     * @param string[] $cacheTags      Cache tags.
     * @param bool     $useSharedCache Whether to save into the shared cache.
     *
     * @return void
     */
    private function set(string $cacheKey, array $cacheData, array $cacheTags, bool $useSharedCache): void
    {
        $this->localCache[$cacheKey] = $cacheData;

        if ($useSharedCache) {
            $this->sharedCache->save(serialize($cacheData), $cacheKey, $cacheTags);
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
        return is_array($cacheData) && array_key_exists('data', $cacheData);
    }
}
