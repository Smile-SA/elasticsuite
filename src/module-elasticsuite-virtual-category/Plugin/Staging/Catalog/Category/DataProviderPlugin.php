<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Staging\Catalog\Category;

use Magento\CatalogStaging\Model\Category\DataProvider as CategoryDataProvider;

/**
 * Extension of the category form UI data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProviderPlugin
{
    /**
     * Remove filter configuration from meta in case of staging form.
     * Remove assign_product configuration from meta in case of staging form.
     * Meta is added in the ui_component via XML.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param \Closure             $proceed      Original method.
     *
     * @return array
     */
    public function aroundGetMeta(CategoryDataProvider $dataProvider, \Closure $proceed)
    {
        $meta = $proceed();

        if (isset($meta['assign_products'])) {
            unset($meta['assign_products']);
        }

        if (isset($meta['display_settings']['children']['facet_config'])) {
            unset($meta['display_settings']['children']['facet_config']);
        }

        return $meta;
    }
}
