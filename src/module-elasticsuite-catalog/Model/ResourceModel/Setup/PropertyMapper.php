<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Benjamin Rosenberger <bensch.rosenberger@gmail.com>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Setup;

use Magento\Eav\Model\Entity\Setup\PropertyMapperAbstract;

/**
 * Elasticsearch catalog EAV attribute mappings.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Benjamin Rosenberger <bensch.rosenberger@gmail.com>
 */
class PropertyMapper extends PropertyMapperAbstract
{
    /**
     * {@inheritdoc}
     */
    public function map(array $input, $entityTypeId)
    {
        return [
            'is_displayed_in_autocomplete' => $this->_getValue($input, 'is_displayed_in_autocomplete', 0),
            'is_used_in_spellcheck' => $this->_getValue($input, 'is_used_in_spellcheck', 0),
            'facet_min_coverage_rate' => $this->_getValue($input, 'facet_min_coverage_rate', 90),
            'facet_max_size' => $this->_getValue($input, 'facet_max_size', 10),
            'facet_sort_order' => $this->_getValue(
                $input,
                'facet_sort_order',
                \Smile\ElasticsuiteCore\Search\Request\BucketInterface::SORT_ORDER_COUNT
            ),
            'display_pattern' => $this->_getValue($input, 'display_pattern', null),
            'display_precision' => $this->_getValue($input, 'display_precision', 0),
            'sort_order_asc_missing' => $this->_getValue($input, 'sort_order_asc_missing', '_last'),
            'sort_order_desc_missing' => $this->_getValue($input, 'sort_order_desc_missing', '_last'),
            'facet_boolean_logic' => $this->_getValue(
                $input,
                'facet_boolean_logic',
                \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface::FILTER_LOGICAL_OPERATOR_OR
            ),
            'is_display_rel_nofollow' => $this->_getValue($input, 'is_display_rel_nofollow', 0),
            'include_zero_false_values' => $this->_getValue($input, 'include_zero_false_values', 0),
        ];
    }
}
