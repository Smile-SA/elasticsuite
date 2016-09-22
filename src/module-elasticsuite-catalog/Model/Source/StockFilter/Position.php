<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Source\StockFilter;

/**
 * Source Model for the Stock Filter Position configuration field
 *
 * This stock filter is a fork of Marius Strajeru ( http://marius-strajeru.blogspot.fr/ ) previous Module
 * available at https://github.com/tzyganu/magento2-stock-filter/
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Position
{
    /**
     * Top Position
     */
    const POSITION_TOP = 'top';

    /**
     * Bottom Position
     */
    const POSITION_BOTTOM = 'bottom';

    /**
     * After Category field Position
     */
    const POSITION_AFTER_CATEGORY = 'after_category';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::POSITION_TOP,
                'label' => __('At the top'),
            ],
            [
                'value' => self::POSITION_BOTTOM,
                'label' => __('At the bottom'),
            ],
            [
                'value' => self::POSITION_AFTER_CATEGORY,
                'label' => __('After the category filter'),
            ],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $values = [];
        foreach ($this->toOptionArray() as $item) {
            $values[$item['value']] = $item['label'];
        }

        return $values;
    }
}
