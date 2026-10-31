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

namespace Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContext;

/**
 * Provides the current value of a context rule search queries can depend on (eg. the customer group).
 *
 * Providers are declared, by context code, in the "providers" di.xml argument of the
 * {@see \Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface} implementation. A provider should
 * return as few distinct values as possible, since the queries depending on its context are cached per value (eg. a
 * zone rather than a list of stores).
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface ProviderInterface
{
    /**
     * Current value of the context.
     *
     * @return string|int|bool|array
     */
    public function getValue();
}
