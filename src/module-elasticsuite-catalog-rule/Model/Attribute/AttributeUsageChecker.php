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

namespace Smile\ElasticsuiteCatalogRule\Model\Attribute;

use Smile\ElasticsuiteCatalogRule\Api\AttributeUsageCheckerInterface;

/**
 * Temporary placeholder implementation for attribute usage validation.
 *
 * Future versions will inspect:
 * - Virtual Category rules
 * - Optimizer rules
 * to determine whether an attribute is definitely referenced by one
 * or more ElasticSuite rule engines (Virtual Categories, Optimizers, etc.).
 *
 * Note: For the time being, this returns `true` as a fail-safe to prevent
 * destructive modifications until explicit parsing logic is implemented.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class AttributeUsageChecker implements AttributeUsageCheckerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param string $attributeCode Attribute code.
     * @return bool
     */
    public function isAttributeUsedInRules(string $attributeCode): bool
    {
        return true;
    }
}
