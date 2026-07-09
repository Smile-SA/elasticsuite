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

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalogRule\Api;

/**
 * This service is responsible for determining whether a specific product
 * attribute is currently referenced by one or more ElasticSuite rule
 * engines (Virtual Categories, Optimizers, etc.).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface AttributeUsageCheckerInterface
{
    /**
     * Check if the attribute is currently used in any ElasticSuite rules.
     *
     * @param string $attributeCode Attribute code.
     * @return bool True if locked within an ElasticSuite rule configuration payload.
     */
    public function isAttributeUsedInRules(string $attributeCode): bool;
}
