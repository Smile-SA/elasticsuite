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
use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocatorInterface;

/**
 * Attribute Usage Checker.
 *
 * This service acts as an adapter between the attribute protection
 * subsystem and the existing rule attribute locator infrastructure.
 *
 * The actual rule detection logic is delegated to the Locator service,
 * which aggregates all registered LocationProviderInterface
 * implementations.
 *
 * Current providers:
 * - Virtual Categories
 * - Optimizers
 *
 * Additional providers can be registered through Dependency Injection
 * without modifying this class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class AttributeUsageChecker implements AttributeUsageCheckerInterface
{
    /**
     * @var LocatorInterface
     */
    private LocatorInterface $attributeLocator;

    /**
     * Constructor.
     *
     * @param LocatorInterface $attributeLocator Rule attribute locator.
     */
    public function __construct(
        LocatorInterface $attributeLocator
    ) {
        $this->attributeLocator = $attributeLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeUsedInRules(string $attributeCode): bool
    {
        if ($attributeCode === '') {
            return false;
        }

        return $this->attributeLocator->isUsedInRules($attributeCode);
    }
}
