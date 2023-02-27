<?php

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Setup;

use Magento\Eav\Model\Entity\Setup\PropertyMapperAbstract;

/**
 * Elasticsearch catalog EAV attribute mappings.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Benjamin Rosenberger (bensch.rosenberger@gmail.com)
 */
class PropertyMapper extends PropertyMapperAbstract
{
    public function map(array $input, $entityTypeId)
    {
        return [
            'is_displayed_in_autocomplete' => $this->_getValue($input, 'is_displayed_in_autocomplete', 0),
            'is_used_in_spellcheck' => $this->_getValue($input, 'is_used_in_spellcheck', 0),
            'facet_min_coverage_rate' => $this->_getValue($input, 'facet_min_coverage_rate', 90),
            'facet_max_size' => $this->_getValue($input, 'facet_max_size', 10),
            'facet_sort_order' => $this->_getValue($input, 'facet_sort_order', 25),
            'display_pattern' => $this->_getValue($input, 'display_pattern', 10),
            'display_precision' => $this->_getValue($input, 'display_precision', 0),
            'sort_order_asc_missing' => $this->_getValue($input, 'sort_order_asc_missing', 10),
            'sort_order_desc_missing' => $this->_getValue($input, 'sort_order_desc_missing', 10),
            'facet_boolean_logic' => $this->_getValue($input, 'facet_boolean_logic', \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface::FILTER_LOGICAL_OPERATOR_OR),
            'is_display_rel_nofollow' => $this->_getValue($input, 'is_display_rel_nofollow', '0'),
            'include_zero_false_values' => $this->_getValue($input, 'include_zero_false_values', 0),
        ];
    }
}
