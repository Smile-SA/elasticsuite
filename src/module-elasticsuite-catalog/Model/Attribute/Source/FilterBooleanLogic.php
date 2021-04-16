<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Attribute\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for multiple values combining logic for filterable attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class FilterBooleanLogic implements OptionSourceInterface
{
    /**
     * Constant for "Logical OR (default)" value.
     *
     * The attribute will combine multiple selected values with a logical OR.
     */
    const BOOLEAN_LOGIC_OR = 0;

    /**
     * Constant for "Logical AND" value.
     *
     * The attribute will combine multiple selected values with a logical AND.
     */
    const BOOLEAN_LOGIC_AND = 1;

    /**
     * Return array of boolean logic operator.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Logical OR (default)'), 'value' => self::BOOLEAN_LOGIC_OR],
            ['label' => __('Logical AND'), 'value' => self::BOOLEAN_LOGIC_AND],
        ];
    }
}
