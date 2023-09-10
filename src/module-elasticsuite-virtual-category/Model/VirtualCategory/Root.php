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
namespace Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory as CategoryCollectionFactory;

/**
 * Model for "Root Category" of virtual categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Root
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Virtual Category Root constructor.
     *
     * @param Registry                  $coreRegistry              Category Repository
     * @param CategoryCollectionFactory $categoryCollectionFactory Category Collection Factory
     * @param CategoryFactory           $categoryFactory           Category Factory
     * @param StoreManagerInterface     $storeManagerInterface     Store Manager
     */
    public function __construct(
        Registry $coreRegistry,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManagerInterface;
    }

    /**
     * Set the currently applied "virtual root category", if any
     *
     * @param CategoryInterface $category The category
     *
     * @return $this
     */
    public function setAppliedRootCategory(CategoryInterface $category)
    {
        $this->coreRegistry->unregister('applied_virtual_root_category');
        $this->coreRegistry->register('applied_virtual_root_category', $category);

        return $this;
    }

    /**
     * Retrieve the currently applied root category, if any.
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getAppliedRootCategory()
    {
        if ($this->coreRegistry->registry('applied_virtual_root_category')) {
            $appliedRoot = $this->coreRegistry->registry('applied_virtual_root_category');
            if ($appliedRoot && $appliedRoot->getId()) {
                return $appliedRoot;
            }
        }

        return null;
    }

    /**
     * Rebuild an URL path for a category under a virtual root category
     *
     * @param CategoryInterface $appliedRootCategory The applied root category
     * @param CategoryInterface $childCategory       The child category to retrieve Url for.
     *
     * @return string
     */
    public function getVirtualCategorySubtreePath($appliedRootCategory, $childCategory)
    {
        $categoryIds = $this->getSubtreePathIds($appliedRootCategory, $childCategory);

        $categories = $this->getCategoriesByIds($categoryIds);

        $urls = [];
        foreach ($categoryIds as $categoryId) {
            $category = $categories[$categoryId];
            $urls[] = $category->getUrlKey();
        }

        return implode('/', $urls);
    }

    /**
     * Retrieve the "virtual category root" currently applied.
     * This is the case when browsing the subtree of a virtual category.
     *
     * @param array $urlKeys Url keys currently applied. Extracted from current Url.
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByUrlKeys($urlKeys)
    {
        $collection = $this->categoryCollectionFactory->create();

        $collection->setStoreId($this->storeManager->getStore()->getId())
            ->addIsActiveFilter()
            ->addNameToResult()
            ->addAttributeToSelect('virtual_category_root')
            ->addAttributeToSelect('virtual_rule')
            ->addAttributeToSelect('url_path')
            ->addAttributeToFilter('url_key', ['in' => $urlKeys])
            ->addAttributeToFilter('is_virtual_category', ['eq' => 1])
            ->addAttributeToFilter('generate_root_category_subtree', ['eq' => 1]);

        return $collection->getFirstItem();
    }

    /**
     * Retrieve a rebuilt path_ids for a given child category under a virtual category subtree.
     *
     * @param CategoryInterface $appliedRoot Applied Root Category
     * @param CategoryInterface $category    Child Category
     *
     * @return array
     */
    public function getSubtreePathIds($appliedRoot, $category)
    {
        $rootPathIds   = array_reverse(explode(',', $appliedRoot->getPathInStore()));
        $appliedRootId = $appliedRoot->getVirtualCategoryRoot();

        array_pop($rootPathIds);
        array_push($rootPathIds, $appliedRoot->getId());

        $pathIds     = $category->getPathIds();
        $pivotIndex  = array_search($appliedRootId, $pathIds);
        $pathIds     = array_slice($pathIds, $pivotIndex + 1);
        $categoryIds = array_merge($rootPathIds, $pathIds);

        return $categoryIds;
    }


    /**
     * Check if a category is configured to use its "virtual root category" to display facets
     *
     * @param CategoryInterface $category The category
     *
     * @return bool
     */
    public function useVirtualRootCategorySubtree($category)
    {
        $useVirtualRootCategorySubtree = false;
        if ($category->getIsVirtualCategory()) {
            $rootCategory = $this->getVirtualCategoryRoot($category);

            $useVirtualRootCategorySubtree = (
                $rootCategory && $rootCategory->getId() && (bool) $category->getGenerateRootCategorySubtree()
            );
        }

        return $useVirtualRootCategorySubtree;
    }

    /**
     * Load the root category used for a virtual category.
     *
     * @param CategoryInterface $category Virtual category.
     *
     * @return CategoryInterface|null
     */
    public function getVirtualCategoryRoot(CategoryInterface $category): ?CategoryInterface
    {
        $storeId      = $category->getStoreId();
        $rootCategory = $this->categoryFactory->create()->setStoreId($storeId);

        if ($category->getVirtualCategoryRoot() !== null && !empty($category->getVirtualCategoryRoot())) {
            $rootCategoryId = $category->getVirtualCategoryRoot();
            $rootCategory->load($rootCategoryId);
        }

        if ($rootCategory && $rootCategory->getId() && ($rootCategory->getLevel() < 1)) {
            $rootCategory = null;
        }

        return $rootCategory;
    }

    /**
     * Retrieve categories by their Ids
     *
     * @param array $categoryIds The category Ids
     *
     * @return CategoryInterface[]
     */
    private function getCategoriesByIds($categoryIds)
    {
        $collection = $this->categoryCollectionFactory->create();

        $collection->addIsActiveFilter()
            ->addUrlRewriteToResult()
            ->addNameToResult()
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('url_key')
            ->addIdFilter($categoryIds);

        return $collection->getItems();
    }
}
