<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;
use Magento\Framework\Registry;

/**
 * Observer that handle proper category display when rendered under subtree of a virtual category root.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DisplaySubtreeCategoryAsProductOnly implements ObserverInterface
{
    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Topmenu constructor.
     *
     * @param VirtualCategoryRoot $virtualCategoryRoot Virtual Category Root
     * @param Registry            $registry            Registry
     */
    public function __construct(VirtualCategoryRoot $virtualCategoryRoot, Registry $registry)
    {
        $this->virtualCategoryRoot = $virtualCategoryRoot;
        $this->registry            = $registry;
    }

    /**
     * Set the category to display mode "product only" if viewed under the subtree of a virtual category root.
     *
     * @param Observer $observer The observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->virtualCategoryRoot->getAppliedRootCategory()) {
            $category = $observer->getCategory();
            $category->setDisplayMode(\Magento\Catalog\Model\Category::DM_PRODUCT);
            if ($this->registry->registry('current_category')) {
                $this->registry->registry('current_category')->setDisplayMode(\Magento\Catalog\Model\Category::DM_PRODUCT);
            }
        }
    }
}
