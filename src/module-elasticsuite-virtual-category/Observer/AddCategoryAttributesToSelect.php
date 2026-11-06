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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Observer;

/**
 * Observer that selects mandatory attributes on category collections
 * to correctly process product loading on 'Virtual Categories'
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @author   Nicolas Graeter <info@graeter-it.de>
 */
class AddCategoryAttributesToSelect implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Observer listening to catalog_category_collection_load_before
     *
     * @param \Magento\Framework\Event\Observer $observer The Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $observer->getCategoryCollection();
        $categoryCollection->addAttributeToSelect(['virtual_category_root', 'is_virtual_category', 'virtual_rule']);
    }
}
