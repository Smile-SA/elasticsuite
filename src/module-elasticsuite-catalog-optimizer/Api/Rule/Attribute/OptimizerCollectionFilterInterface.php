<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Api\Rule\Attribute;

use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;

/**
 * Optimizer Collection Filter Interface.
 *
 * Defines a contract for applying additional constraints to the
 * Optimizer collection when resolving attribute usage.
 *
 * This abstraction allows external modules (e.g. Premium modules)
 * to alter the collection behavior without creating direct dependencies
 * between modules.
 *
 * Typical use cases:
 * - Exclude optimizers linked to A/B Campaigns
 * - Include only specific optimizer types
 * - Apply store or context-specific filtering
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
interface OptimizerCollectionFilterInterface
{
    /**
     * Apply filtering logic to the optimizer collection.
     *
     * Implementations are expected to modify the underlying SELECT query
     * (e.g. JOINs, WHERE conditions).
     *
     * @param Collection $collection Optimizer collection to be filtered.
     *
     * @return void
     */
    public function apply(Collection $collection): void;
}
