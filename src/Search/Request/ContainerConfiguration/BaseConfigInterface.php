<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration;

/**
 * Base Container Configuration interface
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface BaseConfigInterface
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

    /**
     * Retrieve all search containers
     *
     * @return mixed
     */
    public function getContainers();

    /**
     * Retrieve a given search container by its code
     *
     * @param string $code The container code
     *
     * @return mixed
     */
    public function getContainer($code);
}
