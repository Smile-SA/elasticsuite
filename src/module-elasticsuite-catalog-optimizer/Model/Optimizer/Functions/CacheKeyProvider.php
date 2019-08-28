<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Provides the cache key to use when caching a container related boost functions.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 */
class CacheKeyProvider implements CacheKeyProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCacheKey(ContainerConfigurationInterface $containerConfiguration)
    {
        $containerName = $containerConfiguration->getName();
        $storeId       = $containerConfiguration->getStoreId();

        $cacheKey = sprintf("%s_%s_%s", self::CACHE_KEY_PREFIX, $containerName, $storeId);

        return $cacheKey;
    }
}
