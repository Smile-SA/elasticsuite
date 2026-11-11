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

namespace Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product;

/**
 * A special attribute or a nested filter whose search query depends on the context it is built in (eg. the customer
 * group), and not only on the condition itself. The queries embedding it are then cached per value of that context.
 *
 * Only special attributes and nested filters are checked for this interface (by the product conditions query builder).
 * Any other code building a query depending on its context (eg. a condition building its own query) records that
 * dependency while the query is built, through {@see \Smile\ElasticsuiteCatalogRule\Api\Rule\QueryDependencyRecorderInterface}.
 *
 * Each context is identified by a code, whose value is given by a provider declared in the
 * {@see \Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface} providers.
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface ContextAwareInterface
{
    /**
     * Codes of the contexts the search query of the condition depends on (eg. ['customer_group']).
     *
     * @return string[]
     */
    public function getContextDependencies(): array;
}
