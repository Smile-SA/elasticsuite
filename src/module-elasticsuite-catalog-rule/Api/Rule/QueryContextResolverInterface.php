<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Api\Rule;

/**
 * Values of the contexts rule search queries can depend on (eg. the customer group), for the current request.
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface QueryContextResolverInterface
{
    /**
     * Current values of some contexts, as strings usable in a cache key.
     *
     * @param string[] $codes Context codes (eg. ['customer_group']).
     *
     * @return string[] Values, by code.
     * @throws \LogicException When no provider is declared for a code.
     */
    public function getValues(array $codes): array;
}
