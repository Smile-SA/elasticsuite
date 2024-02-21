<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\CacheInterface;

/**
 * ElasticSuite caching related methods.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Cache extends AbstractHelper
{
    /**
     * @var integer
     */
    const DEFAULT_LIFETIME = 7200;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var IndexSettings
     */
    private $indexSettingsHelper;

    /**
     * @var array
     */
    private $localCache = [];

    /**
     * Constructor.
     *
     * @param Context        $context             Helper context.
     * @param CacheInterface $cache               Cache manager.
     * @param IndexSettings  $indexSettingsHelper Index settings helper.
     */
    public function __construct(Context $context, CacheInterface $cache, IndexSettings $indexSettingsHelper)
    {
        parent::__construct($context);
        $this->cache = $cache;
        $this->indexSettingsHelper = $indexSettingsHelper;
    }

    /**
     * Save some data into an index cache.
     *
     * @param string   $cacheKey  Cache key.
     * @param mixed    $data      Data.
     * @param string[] $cacheTags Cache tags.
     * @param integer  $lifetime  Cache lifetime.
     */
    public function saveCache($cacheKey, $data, $cacheTags = [], $lifetime = self::DEFAULT_LIFETIME)
    {
        $this->localCache[$cacheKey] = $data;

        if (!is_string($data)) {
            $data = serialize($data);
        }

        $cacheTags[] = \Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::CACHE_TAG;

        $this->cache->save($data, $cacheKey, $cacheTags, $lifetime);
    }

    /**
     * Load data from the cache
     *
     * @param string $cacheKey Cache key.
     *
     * @return mixed
     */
    public function loadCache($cacheKey)
    {
        if (!isset($this->localCache[$cacheKey])) {
            $data = $this->cache->load($cacheKey);

            if ($data) {
                $data = unserialize($data);
            }

            $this->localCache[$cacheKey] = $data;
        }

        return $this->localCache[$cacheKey];
    }

    /**
     * Clean the cache by index identifier and store.
     *
     * @param string $indexIdentifier Index identifier.
     * @param string $storeId         Store id.
     *
     * @return void
     */
    public function cleanIndexCache($indexIdentifier, $storeId)
    {
        $cacheTags        = $this->getCacheTags($indexIdentifier, $storeId);
        $this->localCache = [];
        $this->cache->clean($cacheTags);
    }

    /**
     * Get cache tag by index identifier / store.
     *
     * @param string $indexIdentifier Index identifier.
     * @param string $storeId         Store id.
     *
     * @return string[]
     */
    private function getCacheTags($indexIdentifier, $storeId)
    {
        return [$this->indexSettingsHelper->getIndexAliasFromIdentifier($indexIdentifier, $storeId)];
    }
}
