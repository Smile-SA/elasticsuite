<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\PageBuilder\Catalog\Sorting;

/**
 * Ensure sorting is properly made :
 *  - previous sorting must be reset : the first order has precedence in Elasticsearch.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OptionPlugin
{
    /**
     * Reset the existing sort orders when applying the sort.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\PageBuilder\Model\Catalog\Sorting\OptionInterface $subject    Legacy sorter
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection    $collection Collection being sorted
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection[]
     */
    public function beforeSort(
        \Magento\PageBuilder\Model\Catalog\Sorting\OptionInterface $subject,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
    ) {
        if (method_exists($collection, 'resetOrder')) {
            $collection->resetOrder();
        }

        return [$collection];
    }
}
