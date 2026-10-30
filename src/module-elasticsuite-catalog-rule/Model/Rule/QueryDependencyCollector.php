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

use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryDependencyRecorderInterface;

/**
 * Collects, while rule search queries are being built, the dependencies of each query, for the caches storing them.
 *
 * Each query build is tracked from start() to stop(). The dependencies recorded during a build (cache tags, expiration
 * date, contexts) are propagated to the enclosing build when it stops, so a query built on top of other queries depends
 * on all their dependencies, however deep the chain is. Dependencies recorded outside of any build are ignored: nothing
 * is collected unless a cache tracks its builds.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryDependencyCollector implements QueryDependencyRecorderInterface
{
    /**
     * @var array[]
     */
    protected $builds = [];

    /**
     * Start tracking the dependencies of a new query build.
     *
     * @return void
     */
    public function start(): void
    {
        $this->builds[] = $this->createBuild();
    }

    /**
     * Stop the current build, propagate its dependencies to the enclosing build and return its cache tags.
     *
     * @return string[]
     */
    public function stop(): array
    {
        $build     = array_pop($this->builds) ?? $this->createBuild();
        $cacheTags = array_keys($build['tags']);
        $this->addCacheTags($cacheTags);
        $this->setExpiration($build['expiresAt']);
        $this->addContextDependencies(array_keys($build['context']));

        return $cacheTags;
    }

    /**
     * {@inheritDoc}
     */
    public function setExpiration(?int $timestamp): void
    {
        if ($timestamp !== null && !empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            $expiresAt    = $this->builds[$currentBuild]['expiresAt'];
            $this->builds[$currentBuild]['expiresAt'] = ($expiresAt === null) ? $timestamp : min($expiresAt, $timestamp);
        }
    }

    /**
     * Expiration date of the query of the current build, as a Unix timestamp. Null if it does not expire.
     *
     * @return int|null
     */
    public function getExpiration(): ?int
    {
        return empty($this->builds) ? null : end($this->builds)['expiresAt'];
    }

    /**
     * {@inheritDoc}
     */
    public function addContextDependencies(array $codes): void
    {
        if (!empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            foreach ($codes as $code) {
                $this->builds[$currentBuild]['context'][$code] = true;
            }
        }
    }

    /**
     * Codes of the contexts the query of the current build depends on, sorted.
     *
     * @return string[]
     */
    public function getContextDependencies(): array
    {
        $codes = empty($this->builds) ? [] : array_keys(end($this->builds)['context']);
        sort($codes);

        return $codes;
    }

    /**
     * Record cache tags (eg. the ones of a query loaded from the cache) as dependencies of the current build.
     *
     * @param string[] $cacheTags Cache tags.
     *
     * @return void
     */
    public function addCacheTags(array $cacheTags): void
    {
        if (!empty($this->builds)) {
            $currentBuild = array_key_last($this->builds);
            foreach ($cacheTags as $cacheTag) {
                $this->builds[$currentBuild]['tags'][$cacheTag] = true;
            }
        }
    }

    /**
     * Whether the query of the current build can be cached. Always true here: a collector recording dependencies on
     * the builds in progress themselves (eg. a query left incomplete because it would embed itself) can tell otherwise.
     *
     * @return bool
     */
    public function isCacheable(): bool
    {
        return true;
    }

    /**
     * Create an empty build.
     *
     * @return array
     */
    protected function createBuild(): array
    {
        return ['tags' => [], 'expiresAt' => null, 'context' => []];
    }
}
