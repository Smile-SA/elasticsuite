<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for each attribute in the Filter Configuration fieldset.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DisplayMode implements OptionSourceInterface
{
    /**
     * Constant for "always hidden" value.
     *
     * This attribute will always be hidden from layered navigation.
     */
    const ALWAYS_HIDDEN    = 0;

    /**
     * Constant for "always displayed" value.
     *
     * This attribute will always be displayed in layered navigation. Even if it has no values.
     */
    const ALWAYS_DISPLAYED = 1;

    /**
     * Constant for "auto display" value.
     *
     * This attribute will rely on "facet coverage rate" to be displayed.
     */
    const AUTO_DISPLAYED   = 2;

    /**
     * Return array of display mode
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Auto'), 'value' => self::AUTO_DISPLAYED],
            ['label' => __('Always hidden'), 'value' => self::ALWAYS_HIDDEN],
            ['label' => __('Always displayed'), 'value' => self::ALWAYS_DISPLAYED],
        ];
    }
}
