<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Search\Request;

/**
 * Search Request Containers Scope interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ContainerScopeInterface
{
    /**
     * Default Scope for configuration
     */
    const SCOPE_DEFAULT = "default";

    /**
     * Container Scope for configuration
     */
    const SCOPE_CONTAINERS = "containers";

    /**
     * Container-Store couple Scope for configuration
     */
    const SCOPE_STORE_CONTAINERS = "containers_stores";
}
