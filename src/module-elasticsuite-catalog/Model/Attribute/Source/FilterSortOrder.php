<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Attribute\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Source model for available sort orders on filterable attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FilterSortOrder implements OptionSourceInterface
{
    /**
     * Return array of available sort order
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => BucketInterface::SORT_ORDER_COUNT, 'label' => __('Result count')],
            ['value' => BucketInterface::SORT_ORDER_MANUAL, 'label' => __('Admin sort')],
            ['value' => BucketInterface::SORT_ORDER_TERM_DEPRECATED, 'label' => __('Name')],
            ['value' => BucketInterface::SORT_ORDER_RELEVANCE, 'label' => __('Relevance')],
        ];
    }
}
