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
namespace Smile\ElasticsuiteCatalog\Model\Category\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Custom sort direction source model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class SortDirection extends AbstractSource
{
    /**
     * Get all options.
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('ASC'), 'value' => 'asc'],
                ['label' => __('DESC'), 'value' => 'desc'],
            ];
        }

        return $this->_options;
    }

    /**
     * Get a text for option value.
     *
     * @param string|int $value Option value.
     *
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }

        return false;
    }
}
