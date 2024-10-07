<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Custom sort direction source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class SortDirectionConfig implements OptionSourceInterface
{
    /**
     * Get options in "value-label" format.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'asc', 'label' => __('ASC')],
            ['value' => 'desc', 'label' => __('DESC')],
        ];
    }
}
