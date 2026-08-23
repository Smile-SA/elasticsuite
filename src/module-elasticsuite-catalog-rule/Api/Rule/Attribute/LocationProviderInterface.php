<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute;

/**
 * Attribute Location Provider Interface.
 *
 * A location provider is responsible for checking whether a given attribute
 * is present in a specific rule source (e.g., catalog rules, search rules, etc.).
 *
 * Multiple providers can be registered and combined via the Locator service.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface LocationProviderInterface
{
    /**
     * Check if attribute is present in the provider's rule context.
     *
     * @param string $attribute Attribute code.
     * @return bool
     */
    public function isPresent(string $attribute): bool;
}
