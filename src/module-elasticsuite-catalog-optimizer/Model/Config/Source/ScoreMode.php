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
 * Score Mode value config source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class ScoreMode implements ArrayInterface
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
                'value' => FunctionScore::SCORE_MODE_MULTIPLY,
                'label' => __('Multiply'),
            ],
            [
                'value' => FunctionScore::SCORE_MODE_SUM,
                'label' => __('Sum'),
            ],
            [
                'value' => FunctionScore::SCORE_MODE_AVG,
                'label' => __('Average'),
            ],
            [
                'value' => FunctionScore::SCORE_MODE_FIRST,
                'label' => __('First'),
            ],
            [
                'value' => FunctionScore::SCORE_MODE_MAX,
                'label' => __('Max'),
            ],
            [
                'value' => FunctionScore::SCORE_MODE_MIN,
                'label' => __('Min'),
            ],
        ];
    }
}
