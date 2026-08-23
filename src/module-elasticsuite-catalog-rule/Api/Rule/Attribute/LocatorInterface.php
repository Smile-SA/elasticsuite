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
 * Attribute Locator Interface.
 *
 * This service is responsible for determining whether a given attribute is used within any rule context.
 *
 * It delegates the actual detection logic to multiple LocationProviderInterface implementations,
 * allowing modular and extensible rule detection.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface LocatorInterface
{
    /**
     * Check if attribute is used in any rule.
     *
     * This method iterates over all registered location providers
     * and returns true as soon as one confirms the attribute is used.
     *
     * @param string $attribute Attribute code.
     * @return bool
     */
    public function isUsedInRules(string $attribute): bool;
}
