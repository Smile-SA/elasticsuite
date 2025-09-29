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

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;
use Magento\Framework\Registry;

/**
 * Observer that handle to set the virtual category root
 * and define proper category display when rendered under subtree of a virtual category root.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ManageVirtualCategoryWithSubtree implements ObserverInterface
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
    public function execute(Observer $observer)
    {
        $category = $observer->getCategory();

        if ($category->getData('is_virtual_category')) {
            $action = $observer->getControllerAction();
            if ($action->getRequest()->getParam('cat')) {
                 $this->virtualCategoryRoot->setAppliedRootCategory($category);
            }
        }

        if ($this->virtualCategoryRoot->getAppliedRootCategory()) {
            $category->setDisplayMode(Category::DM_PRODUCT);
            if ($this->registry->registry('current_category')) {
                $this->registry->registry('current_category')->setDisplayMode(Category::DM_PRODUCT);
            }
        }
    }
}
