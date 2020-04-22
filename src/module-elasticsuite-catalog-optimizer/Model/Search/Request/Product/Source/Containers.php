<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Search\Request\Product\Source;

/**
 * Source model for search request containers related to products only.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Containers extends \Smile\ElasticsuiteCore\Model\Search\Request\Source\Containers
{
    /**
     * Product document type.
     */
    const PRODUCT_INDEX = 'catalog_product';

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getContainers() as $container) {
            if (isset($container['index']) && ($container['index'] === self::PRODUCT_INDEX)) {
                $options[] = [
                    'value'    => $container['name'],
                    'label'    => __($container['label']),
                    'fulltext' => isset($container['fulltext']) && $container['fulltext'] == "true" ? true : false,
                ];
            }
        }

        return $options;
    }
}
