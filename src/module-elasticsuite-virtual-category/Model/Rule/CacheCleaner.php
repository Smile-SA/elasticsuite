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

use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;

/**
 * Cleans the virtual rules cache entries depending on some categories.
 *
 * A change on a category impacts the queries depending on it, but also the queries depending on any of its ancestors,
 * since the query of a category embeds the queries of its virtual descendants.
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
     * @var DependencyCollector
     */
    private $dependencyCollector;

    /**
     * @var CategoryResource
     */
    private $categoryResource;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * @var RootCategoryProvider
     */
    private $rootCategoryProvider;

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache    $cache                Elasticsuite cache.
     * @param DependencyCollector  $dependencyCollector  Virtual rules dependency collector.
     * @param CategoryResource     $categoryResource     Category resource model.
     * @param QueryCache           $queryCache           Virtual rules query cache.
     * @param RootCategoryProvider $rootCategoryProvider Virtual root categories provider.
     */
    public function __construct(
        ElasticsuiteCache $cache,
        DependencyCollector $dependencyCollector,
        CategoryResource $categoryResource,
        QueryCache $queryCache,
        RootCategoryProvider $rootCategoryProvider
    ) {
        $this->cache                = $cache;
        $this->dependencyCollector  = $dependencyCollector;
        $this->categoryResource     = $categoryResource;
        $this->queryCache           = $queryCache;
        $this->rootCategoryProvider = $rootCategoryProvider;
    }

    /**
     * Clean the cache entries depending on the given categories, once the current transaction (if any) is committed.
     * Cleaning before the commit would allow a concurrent request to cache again a query built from outdated data.
     *
     * @param int[] $categoryIds Category ids, which must include the changed categories ancestors.
     *
     * @return void
     */
    public function cleanAfterCommit(array $categoryIds): void
    {
        if ($this->categoryResource->getConnection()->getTransactionLevel() > 0) {
            $this->categoryResource->addCommitCallback(function () use ($categoryIds) {
                $this->clean($categoryIds);
            });

            return;
        }

        $this->clean($categoryIds);
    }

    /**
     * Clean the cache entries depending on the given categories or on any of their ancestors.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function cleanWithAncestors(array $categoryIds): void
    {
        $this->cleanAfterCommit($this->getPathIds($categoryIds));
    }

    /**
     * Clean the cache entries depending on the given categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function clean(array $categoryIds): void
    {
        $cacheTags = $this->dependencyCollector->getCacheTags($categoryIds);

        if (!empty($cacheTags)) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cacheTags);
            // Queries and root categories kept by the current process may be outdated as well.
            $this->queryCache->reset();
            $this->rootCategoryProvider->reset();
        }
    }

    /**
     * Ids of the given categories and of all their ancestors.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return int[]
     */
    private function getPathIds(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $connection = $this->categoryResource->getConnection();
        $select     = $connection->select()
            ->distinct()
            ->from($this->categoryResource->getEntityTable(), ['path'])
            ->where('entity_id IN (?)', array_map('intval', $categoryIds));

        $pathIds = [];
        foreach ($connection->fetchCol($select) as $path) {
            $pathIds = array_merge($pathIds, explode('/', (string) $path));
        }

        return array_values(array_unique(array_map('intval', $pathIds)));
    }
}
