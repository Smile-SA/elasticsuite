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
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer functions provider interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ProviderInterface
{
    const TYPE_DEFAULT = 'default';
    const TYPE_EXCLUDE = 'exclude';
    const TYPE_REPLACE = 'replace';

    /**
     * Retrieve Optimizers functions for a given Container
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     *
     * @return array
     */
    public function getFunctions(ContainerConfigurationInterface $containerConfiguration);

    /**
     * Retrieve provider type
     *
     * @return string
     */
    public function getType();
}
