<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer collection provider interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ProviderInterface
{
    const TYPE_DEFAULT = 'default';
    const TYPE_EXCLUDE = 'exclude';
    const TYPE_ONLY    = 'only';
    const TYPE_REPLACE = 'replace';

    /**
     * Retrieve Optimizers collection for a given Container
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    public function getCollection(ContainerConfigurationInterface $containerConfiguration);

    /**
     * If this provider allows caching of computed optimizers
     *
     * @return boolean
     */
    public function useCache();

    /**
     * Retrieve provider type
     *
     * @return string
     */
    public function getType();
}
