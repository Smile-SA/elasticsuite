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

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule;

use Magento\Catalog\Model\Category;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Cleans the virtual rules cache entries of some categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CacheCleaner
{
    /**
     * @var ElasticsuiteCache
     */
    private $cache;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache $cache      Elasticsuite cache.
     * @param QueryCache        $queryCache Virtual rules query cache.
     */
    public function __construct(ElasticsuiteCache $cache, QueryCache $queryCache)
    {
        $this->cache      = $cache;
        $this->queryCache = $queryCache;
    }

    /**
     * Clean the cache entries of the given categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function clean(array $categoryIds): void
    {
        $cacheTags = array_map(
            function ($categoryId) {
                return Category::CACHE_TAG . '_' . $categoryId;
            },
            array_values($categoryIds)
        );

        if (!empty($cacheTags)) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cacheTags);
            // Queries kept by the current process may be outdated as well.
            $this->queryCache->reset();
        }
    }
}
