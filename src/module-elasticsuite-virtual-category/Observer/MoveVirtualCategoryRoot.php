<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Observer to ensure validity of category move operations,
 * to prevent moving a category under another one which uses already it as virtual category root
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MoveVirtualCategoryRoot implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * MoveVirtualCategoryRoot constructor.
     *
     * @SuppressWarnings(PHPMD.LongVariableName)
     *
     * @param \Magento\Store\Model\StoreManagerInterface                      $storeManager              Store Manager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory Category Collection Factory
     */
    public function __construct(StoreManagerInterface $storeManager, CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Process validation of the move process of a category :
     * a category cannot be moved under a category using it as virtual root.
     *
     * @event catalog_category_move_before
     *
     * @param Observer $observer The observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Category $category The moved Category */
        $category = $observer->getCategory();

        /** @var \Magento\Catalog\Model\Category $newParent */
        $newParent = $observer->getParent();
        $newParentCategories = $this->getNewParentTree($newParent);

        foreach ($newParentCategories as $parentCategory) {
            $parentIsVirtual = (bool) $parentCategory->getIsVirtualCategory() === true;
            $parentRootCategoryId = (int) $parentCategory->getVirtualCategoryRoot();

            if ($parentIsVirtual && ($parentRootCategoryId === (int) $category->getId())) {
                $message = "Cannot move the category : '%2' is using '%1' as virtual root category.";
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($message, $category->getName(), $parentCategory->getName())
                );
            }
        }
    }

    /**
     * Return parent categories of category
     *
     * @param \Magento\Catalog\Model\Category $category The category
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    private function getNewParentTree($category)
    {
        $pathIds = array_reverse(explode(',', $category->getPathInStore()));
        $pathIds[] = $category->getId();

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
        $categories = $this->categoryCollectionFactory->create();

        return $categories->setStore($this->storeManager->getStore())
            ->addAttributeToSelect(['name', 'is_virtual_category', 'virtual_category_root'])
            ->addFieldToFilter('entity_id', ['in' => $pathIds]);
    }
}
