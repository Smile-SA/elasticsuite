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
}
