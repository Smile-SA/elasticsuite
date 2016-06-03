<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteVirtualCategory\Observer;

use Magento\Catalog\Block\Adminhtml\Category\Tabs;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Handles additional tab for merchandising in the catagory edit page.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryProductFormObserver implements ObserverInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var Tabs $tabs
         */
        $tabs = $observer->getEvent()->getTabs();

        $virtualCategoryFormBlock = $tabs->getLayout()->createBlock(
            'Smile\ElasticSuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Edit\Tab\Merchandising',
            'category.merchandising.form'
        );

        if ($this->canDisplayTab($virtualCategoryFormBlock->getCategory())) {
            $tabs->addTab(
                'category.merchandising',
                ['label' => __('Merchandising'), 'content' => $virtualCategoryFormBlock->toHtml()]
            );
        }
    }

    /**
     * Indicates if the merchandising tab can be displayed on the current category.
     *
     * @param CategoryInterface $category Current category.
     *
     * @return boolean
     */
    private function canDisplayTab(CategoryInterface $category)
    {
        return $category->getId() !== null && $category->getLevel() > 1;
    }
}
