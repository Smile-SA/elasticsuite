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

/**
 * Topmenu observer for virtual categories.
 * Used to properly set "has_active" property on categories when being viewed under a virtual category subtree
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Topmenu implements ObserverInterface
{
    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * Topmenu constructor.
     *
     * @param VirtualCategoryRoot $virtualCategoryRoot Virtual Category Root
     */
    public function __construct(VirtualCategoryRoot $virtualCategoryRoot)
    {
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * @param Observer $observer The observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $appliedRoot = $this->virtualCategoryRoot->getAppliedRootCategory();
        if ($appliedRoot && $appliedRoot->getPath()) {
            foreach ($observer->getMenu()->getChildren() as $category) {
                $categoryId = preg_replace("/[^0-9]/", "", $category->getId());
                $hasActive  = in_array((string) $categoryId, explode('/', $appliedRoot->getPath()), true);
                $category->setHasActive($hasActive);
            }
        }
    }
}
