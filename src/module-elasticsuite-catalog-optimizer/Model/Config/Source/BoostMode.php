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
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;

/**
 * Boost Mode value config source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class BoostMode implements ArrayInterface
{
    /**
     * Returns option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => FunctionScore::BOOST_MODE_MULTIPLY,
                'label' => __('Multiply'),
            ],
            [
                'value' => FunctionScore::BOOST_MODE_REPLACE,
                'label' => __('Replace'),
            ],
            [
                'value' => FunctionScore::BOOST_MODE_SUM,
                'label' => __('Sum'),
            ],
            [
                'value' => FunctionScore::BOOST_MODE_AVG,
                'label' => __('Average'),
            ],
            [
                'value' => FunctionScore::BOOST_MODE_MAX,
                'label' => __('Max'),
            ],
            [
                'value' => FunctionScore::BOOST_MODE_MIN,
                'label' => __('Min'),
            ],
        ];
    }
}
