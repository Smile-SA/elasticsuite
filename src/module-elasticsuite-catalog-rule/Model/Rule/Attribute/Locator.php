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

namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Attribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocatorInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocationProviderInterface;

/**
 * Attribute Locator.
 *
 * Default implementation of LocatorInterface.
 *
 * This class aggregates multiple LocationProviderInterface instances
 * and determines whether an attribute is used in any rule by delegating
 * the check to each provider.
 *
 * The first provider returning TRUE will short-circuit the process.
 */
class Locator implements LocatorInterface
{
    /**
     * @var LocationProviderInterface[]
     */
    private array $locationProviders;

    /**
     * Constructor.
     *
     * @param LocationProviderInterface[] $locationProviders List of providers injected via DI.
     */
    public function __construct(array $locationProviders = [])
    {
        $this->locationProviders = $locationProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInRules(string $attribute): bool
    {
        foreach ($this->locationProviders as $provider) {
            // Defensive check to ensure correct DI configuration.
            if (!$provider instanceof LocationProviderInterface) {
                continue;
            }

            if ($provider->isPresent($attribute)) {
                return true;
            }
        }

        return false;
    }
}
