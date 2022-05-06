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
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Helper;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Url as UrlModel;

/**
 * Plugin on Catalog Helper Data, to ensure breadcrumb is correctly displayed when browsing a virtual category subtree.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class BreadcrumbPlugin
{
    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * @var null
     */
    private $categoryPath;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Url
     */
    private $urlModel;

    /**
     * BreadCrumb plugin constructor.
     *
     * @param VirtualCategoryRoot       $virtualCategoryRoot       Virtual Category Root
     * @param CategoryCollectionFactory $categoryCollectionFactory Category Collection Factory
     * @param UrlModel                  $urlModel                  Category URL Model
     */
    public function __construct(
        VirtualCategoryRoot $virtualCategoryRoot,
        CategoryCollectionFactory $categoryCollectionFactory,
        UrlModel $urlModel
    ) {
        $this->virtualCategoryRoot = $virtualCategoryRoot;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlModel = $urlModel;
    }

    /**
     * Modify breadcrumb path if needed :
     *  - if viewing a category under the subtree of a virtual category.
     *
     * @param \Magento\Catalog\Helper\Data $helper  Catalog Helper
     * @param \Closure                     $proceed The Catalog Helper getBreadcrumbPath() function
     *
     * @return array
     */
    public function aroundGetBreadcrumbPath(\Magento\Catalog\Helper\Data $helper, \Closure $proceed)
    {
        $appliedRoot = $this->virtualCategoryRoot->getAppliedRootCategory();
        if ($appliedRoot) {
            return $this->getSubtreeBreadcrumbPath($helper, $appliedRoot);
        }

        return $proceed();
    }

    /**
     * Retrieve Breadcrumb for a category viewed under a virtual root category subtree.
     *
     * @param \Magento\Catalog\Helper\Data    $helper      Catalog Helper
     * @param \Magento\Catalog\Model\Category $appliedRoot Currently applied root category
     *
     * @return array
     */
    private function getSubtreeBreadcrumbPath($helper, $appliedRoot)
    {
        if (!$this->categoryPath) {
            $path     = [];
            $category = $helper->getCategory();

            if ($category) {
                $appliedRootId = $appliedRoot->getVirtualCategoryRoot();
                $pathIds       = array_reverse(explode(',', $category->getPathInStore()));
                $pathIds       = array_slice($pathIds, array_search($appliedRootId, $pathIds) + 1);

                $categoryIds = $this->virtualCategoryRoot->getSubtreePathIds($appliedRoot, $category);
                $categories  = $this->getCategoriesByIds($categoryIds);
                foreach ($categoryIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $link = '';
                        if ($this->isCategoryLink($helper, $categoryId)) {
                            $link = $this->getCategoryUrl($categories[$categoryId], $pathIds);
                        }
                        $path['category' . $categoryId] = [
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $link,
                        ];
                    }
                }
            }

            if ($helper->getProduct()) {
                $path['product'] = ['label' => $helper->getProduct()->getName()];
            }

            $this->categoryPath = $path;
        }

        return $this->categoryPath;
    }

    /**
     * Retrieve a category URL for Breadcrumb item.
     *
     * @param CategoryInterface $category Category
     * @param array             $pathIds  Path Ids of the categories belonging to the subtree
     *
     * @return string
     */
    private function getCategoryUrl($category, $pathIds)
    {
        $url = $category->getUrl();

        // This category is under the subtree, recompute its url.
        if (in_array($category->getId(), $pathIds)) {
            $url = $this->urlModel->getVirtualCategorySubtreeUrl(
                $this->virtualCategoryRoot->getAppliedRootCategory(),
                $category
            );
        }

        return $url;
    }

    /**
     * Check is category link
     *
     * @param \Magento\Catalog\Helper\Data $helper     Catalog Helper
     * @param int                          $categoryId Category Id
     *
     * @return bool
     */
    private function isCategoryLink($helper, $categoryId)
    {
        if ($helper->getProduct()) {
            return true;
        }
        if ($categoryId != $helper->getCategory()->getId()) {
            return true;
        }

        return false;
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
