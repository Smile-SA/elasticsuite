<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\LayeredNavigation\Save;

/**
 * Override of plugin \Magento\LayeredNavigation\Plugin\Save\AdjustAttributeSearchable introduced in Magento 2.4.7,
 * that prevents an attribute from being set to is_filterable_in_search=1 when is_searchable=0.
 * Since the class \Magento\LayeredNavigation\Plugin\Save\AdjustAttributeSearchable does not exist in Magento 2.4.6*,
 * simply disabling the plugin through the DI.xml was not an option.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class AdjustAttributeSearchable
{
    // This plugin does not modify anything, but it must be defined to avoid a fatal error at setup:di:compile time.
}
