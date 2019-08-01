<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Container optimizer functions list cache key provider interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 */
interface CacheKeyProviderInterface
{
    /**
     * @var string
     */
    const CACHE_KEY_PREFIX = 'optimizer_boost_function';

    /**
     * Returns the cache key to use when caching the list of boost functions applied to a container.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return string
     */
    public function getCacheKey(ContainerConfigurationInterface $containerConfiguration);
}
