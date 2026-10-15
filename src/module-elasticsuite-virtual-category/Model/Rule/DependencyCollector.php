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

use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryDependencyCollector;

/**
 * Collects, while virtual rules queries are being built, the categories each query depends on.
 *
 * Every category whose data is read during a query build is recorded as a cache tag of the current build, propagated
 * to the enclosing builds like any other dependency (see the parent class), so a query built on top of other
 * categories queries depends on all their dependencies, however deep the chain is.
 *
 * Builds also record truncations: to avoid infinite recursion, a category whose query is already being built
 * (an "excluded" category) is left out of the queries built meanwhile. Those queries are then only valid in the
 * context of the excluded category build, and must not be cached. The truncation is propagated to the enclosing
 * builds until the build of the excluded category itself is reached, where it is resolved.
 *
 * Builds finally record the embedded categories: the categories whose query (or id) is embedded in the query being
 * built, and the virtual roots path of the embedded virtual categories. A cached query can only be reused while
 * building other queries if none of its embedded categories is being built: that would change its result, while
 * merely depending on such a category (eg. a virtual child left out) would not.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DependencyCollector extends QueryDependencyCollector
{
    /**
     * Prefix of the cache tag identifying the virtual rules cache entries depending on a given category.
     */
    const CACHE_TAG_PREFIX = 'ES_VCAT_';

    /**
     * Stop the current build, propagate its dependencies, unresolved truncations and embedded categories to the
     * enclosing build and return its cache tags.
     *
     * @return string[]
     */
    public function stop(): array
    {
        $build     = empty($this->builds) ? $this->createBuild() : end($this->builds);
        $cacheTags = parent::stop();
        $this->addTruncation(array_keys($build['truncatedBy']));
        $this->addEmbedded(array_keys($build['embedded']));

        return $cacheTags;
    }

    /**
     * Whether the query of the current build can be cached: not if it left out excluded categories not resolved yet.
     *
     * @return bool
     */
    public function isCacheable(): bool
    {
        return !$this->isTruncated();
    }

    /**
     * Record that the query of the current build left out some excluded categories.
     *
     * @param int[] $categoryIds Ids of the excluded categories which were left out.
     *
     * @return void
     */
    public function addTruncation(array $categoryIds): void
    {
        if (!empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            foreach ($categoryIds as $categoryId) {
                $this->builds[$currentBuild]['truncatedBy'][(int) $categoryId] = true;
            }
        }
    }

    /**
     * Resolve, in the current build, the truncations caused by the exclusion of the given categories.
     * To be called by the build of these categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function resolveTruncation(array $categoryIds): void
    {
        if (!empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            foreach ($categoryIds as $categoryId) {
                unset($this->builds[$currentBuild]['truncatedBy'][(int) $categoryId]);
            }
        }
    }

    /**
     * Whether the query of the current build left out some excluded categories not resolved yet.
     * Such a query depends on the context it was built in and must not be cached.
     *
     * @return bool
     */
    public function isTruncated(): bool
    {
        return !empty($this->builds) && !empty(end($this->builds)['truncatedBy']);
    }

    /**
     * Record categories as embedded in the query of the current build.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function addEmbedded(array $categoryIds): void
    {
        if (!empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            foreach ($categoryIds as $categoryId) {
                $this->builds[$currentBuild]['embedded'][(int) $categoryId] = true;
            }
        }
    }

    /**
     * Forget the categories embedded in the current build (eg. when its query is finally left out).
     *
     * @return void
     */
    public function discardEmbedded(): void
    {
        if (!empty($this->builds)) {
            $this->builds[array_key_last($this->builds)]['embedded'] = [];
        }
    }

    /**
     * Categories embedded in the query of the current build.
     *
     * @return int[]
     */
    public function getEmbedded(): array
    {
        return empty($this->builds) ? [] : array_keys(end($this->builds)['embedded']);
    }

    /**
     * Record categories as dependencies of the current build.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function addCategoryIds(array $categoryIds): void
    {
        $this->addCacheTags($this->getCacheTags($categoryIds));
    }

    /**
     * Cache tags of the virtual rules cache entries depending on the given categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return string[]
     */
    public function getCacheTags(array $categoryIds): array
    {
        $cacheTags = [];
        foreach ($categoryIds as $categoryId) {
            $cacheTags[] = self::CACHE_TAG_PREFIX . (int) $categoryId;
        }

        return array_values(array_unique($cacheTags));
    }

    /**
     * Create an empty build.
     *
     * @return array
     */
    protected function createBuild(): array
    {
        return parent::createBuild() + ['truncatedBy' => [], 'embedded' => []];
    }
}
