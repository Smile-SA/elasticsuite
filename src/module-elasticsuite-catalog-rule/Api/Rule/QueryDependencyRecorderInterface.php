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
 * Records the dependencies of the rule search query being built (eg. when the rule queries are cached).
 *
 * Any code changing the search query of a rule condition according to the moment it is built (eg. a date bound
 * computed from the current date) or to its context (eg. "is discount" conditions evaluated on the offers of the
 * physical store/retailer chosen by the customer) should record that dependency while the query is built. A filter
 * applying to all the products (eg. only the products of that store/retailer) does not belong in rule queries: it is a
 * filter of the search request container.
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface QueryDependencyRecorderInterface
{
    /**
     * Record that the rule search query being built is only valid until the given date.
     *
     * @param int|null $timestamp Expiration date as a Unix timestamp. Null if the query does not expire.
     *
     * @return void
     */
    public function setExpiration(?int $timestamp): void;

    /**
     * Record that the rule search query being built depends on some contexts.
     *
     * @param string[] $codes Context codes (eg. ['customer_group']).
     *
     * @return void
     */
    public function addContextDependencies(array $codes): void;
}
