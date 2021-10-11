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
namespace Smile\ElasticsuiteVirtualCategory\Plugin\CatalogGraphQl\Model\Config;

use Magento\CatalogGraphQl\Model\Config\CategoryAttributeReader;

/**
 * Plugin that will remove "virtual_rule" field for GraphQL.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryAttributeReaderPlugin
{
    /**
     * Remove "virtual_rule" after reading category attributes.
     * Magento does the same with "position", "layout_update", etc...
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\CatalogGraphQl\Model\Config\CategoryAttributeReader $subject Category Attribute Reader
     * @param array                                                        $result  Legacy result
     *
     * @return array
     */
    public function afterRead(CategoryAttributeReader $subject, $result)
    {
        $types = ["CategoryInterface", "CategoryTree"];

        foreach ($types as $type) {
            if (isset($result[$type]) && isset($result[$type]['fields'])) {
                if (isset($result[$type]['fields']['virtual_rule'])) {
                    unset($result[$type]['fields']['virtual_rule']);
                }
            }
        }

        return $result;
    }
}
