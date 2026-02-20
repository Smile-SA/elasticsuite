<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\Filter;

use Magento\Framework\App\CacheInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * PreBuilder of function score queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 */
class PreBuilder
{
    /** @var int default lifetime for computed optimizers functions. */
    const DEFAULT_CACHE_LIFETIME = 7200;

    /** @var Builder */
    private $builder;

    /** @var CacheInterface */
    private $cache;

    /** @var integer|mixed */
    private $cacheLifeTime;

    /** @var array */
    private $prebuiltFilters;

    /**
     * Constructor.
     *
     * @param Builder        $builder       Adapter request builder.
     * @param CacheInterface $cache         Cache manager.
     * @param int            $cacheLifetime Cache Lifetime of prebuilt optimizers function filters.
     */
    public function __construct(
        Builder $builder,
        CacheInterface $cache,
        $cacheLifetime = self::DEFAULT_CACHE_LIFETIME
    ) {
        $this->builder = $builder;
        $this->cache = $cache;
        $this->cacheLifeTime = $cacheLifetime;
    }

    /**
     * Prebuild function queries of optimizers and apply cache.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param array                           $functions              Functions whose queries to prebuild.
     *
     * @return array
     */
    public function prebuild(ContainerConfigurationInterface $containerConfiguration, $functions)
    {
        // Test if enabled.
        foreach ($functions as $optimizerId => &$function) {
            if (isset($function['filter'])) {
                if ($function['filter'] instanceof QueryInterface) {
                    $function['filter'] = $this->prebuildFilter(
                        $containerConfiguration,
                        $optimizerId,
                        $function['filter']
                    );
                }
            }
        }

        return $functions;
    }

    /**
     * Prebuild an optimizer function filter query into a portion of Elasticsearch request.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param int                             $optimizerId            Optimizer id.
     * @param QueryInterface                  $filter                 Filter query.
     *
     * @return array
     */
    private function prebuildFilter(ContainerConfigurationInterface $containerConfiguration, $optimizerId, QueryInterface $filter)
    {
        $cacheKey = sprintf(
            "%s_%s_%s",
            'optimizer_boost_function_prebuild',
            $containerConfiguration->getStoreId(),
            $optimizerId
        );

        if (!isset($this->prebuiltFilters[$cacheKey])) {
            if ($prebuiltFilter = $this->cache->load($cacheKey)) {
                $prebuiltFilter = unserialize($prebuiltFilter);
            } else {
                $prebuiltFilter = $this->builder->buildQuery($filter);
                $this->cache->save(serialize($prebuiltFilter), $cacheKey, [Optimizer::CACHE_TAG], $this->cacheLifeTime);
            }

            $this->prebuiltFilters[$cacheKey] = $prebuiltFilter;
        }

        return $this->prebuiltFilters[$cacheKey];
    }
}
