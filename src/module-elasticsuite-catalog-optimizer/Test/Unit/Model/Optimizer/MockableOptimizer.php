<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Model\Optimizer;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * Mockable Optimizer class for testing purposes.
 * Adds magic methods as concrete ones because PHPUnit no longer supports 'addMethods'.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 */
class MockableOptimizer extends Optimizer
{
    /**
     * Return the names of the containers the optimizer is applicable to.
     *
     * @return string[]|null
     */
    public function getSearchContainer(): ?array
    {
        return ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'];
    }

    /**
     * Return the limitation configuration for the (quick) search container(s).
     *
     * @return int[]|null
     */
    public function getQuickSearchContainer(): ?array
    {
        return ['apply_to' => 0];
    }

    /**
     * Return the limitation configuration for the catalog view container.
     *
     * @return int[]|null
     */
    public function getCatalogViewContainer(): ?array
    {
        return ['apply_to' => 0];
    }
}
