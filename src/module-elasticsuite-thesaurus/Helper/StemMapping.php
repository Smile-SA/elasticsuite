<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Helper;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Stem mapping cache helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class StemMapping
{
    /**
     * @var string
     */
    const CACHE_KEY_PREFIX = 'elasticsuite_stem_mapping_';

    /**
     * @var string
     */
    const CACHE_TAG = 'ELASTICSUITE_STEM_MAPPING';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor.
     *
     * @param CacheInterface      $cache      Magento cache interface.
     * @param SerializerInterface $serializer Serializer.
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * Save stem mapping to cache.
     *
     * @param int   $storeId Store id.
     * @param array $mapping Stem to original term mapping.
     *
     * @return void
     */
    public function saveMapping(int $storeId, array $mapping)
    {
        if (empty($mapping)) {
            return;
        }

        $cacheKey = $this->getCacheKey($storeId);
        $this->cache->save($this->serializer->serialize($mapping), $cacheKey, [self::CACHE_TAG]);
    }

    /**
     * Get stem mapping from cache.
     *
     * @param int $storeId Store id.
     *
     * @return array
     */
    public function getMapping(int $storeId): array
    {
        $cacheKey = $this->getCacheKey($storeId);
        $data = $this->cache->load($cacheKey);

        if ($data) {
            return $this->serializer->unserialize($data);
        }

        return [];
    }

    /**
     * Clear stem mapping cache.
     *
     * @param int $storeId Store id.
     *
     * @return void
     */
    public function clearMapping(int $storeId)
    {
        $cacheKey = $this->getCacheKey($storeId);
        $this->cache->remove($cacheKey);
    }

    /**
     * Get cache key for store.
     *
     * @param int $storeId Store id.
     *
     * @return string
     */
    private function getCacheKey(int $storeId): string
    {
        return self::CACHE_KEY_PREFIX . $storeId;
    }
}
