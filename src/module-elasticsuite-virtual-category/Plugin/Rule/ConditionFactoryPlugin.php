<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Rule;

use Magento\Rule\Model\ConditionFactory;

/**
 * Rule condition factory plugin to on-the-fly combine and product rule model change.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class ConditionFactoryPlugin
{
    /**
     * Before plugin - Create new object for each requested model.
     * If model is requested first time, store it at array.
     * It's made by performance reasons to avoid initialization of same models each time when rules are being processed.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ConditionFactory $factory Rule condition factory.
     * @param string           $type    Condition model.
     *
     * @return array
     */
    public function beforeCreate(ConditionFactory $factory, $type)
    {
        if ($type == \Magento\CatalogWidget\Model\Rule\Condition\Product::class) {
            $type = \Smile\ElasticsuiteVirtualCategory\Model\Rule\WidgetCondition\Product::class;
        }
        if ($type == \Magento\CatalogWidget\Model\Rule\Condition\Combine::class) {
            $type = \Smile\ElasticsuiteVirtualCategory\Model\Rule\WidgetCondition\Combine::class;
        }

        return [$type];
    }
}
