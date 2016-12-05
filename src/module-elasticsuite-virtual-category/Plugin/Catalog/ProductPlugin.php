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
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;
use Smile\ElasticsuiteVirtualCategory\Model\Url;

/**
 * Product Plugin for Virtual Categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ProductPlugin
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Url
     */
    private $urlModel;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * ProductPlugin constructor.
     *
     * @param Url                         $urlModel            Virtual Categories URL Model
     * @param CategoryRepositoryInterface $categoryRepository  Category Repository
     * @param VirtualCategoryRoot         $virtualCategoryRoot Virtual Category Root Model
     */
    public function __construct(
        Url $urlModel,
        CategoryRepositoryInterface $categoryRepository,
        VirtualCategoryRoot $virtualCategoryRoot
    ) {
        $this->urlModel           = $urlModel;
        $this->categoryRepository = $categoryRepository;
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * Append virtual category Url to product request path, if needed.
     *
     * @param \Magento\Catalog\Model\Product $product The Product
     * @param \Closure                       $proceed The legacy getProductUrl method
     * @param null                           $useSid  If the SID should be used or not
     *
     * @return mixed
     */
    public function aroundGetProductUrl(Product $product, \Closure $proceed, $useSid = null)
    {
        $requestPath = $product->getRequestPath();
        if (empty($requestPath) || $this->getAppliedRootCategory()) {
            $requestPath = $this->getRequestPath($product);
            if (null !== $requestPath) {
                $product->setRequestPath($requestPath);
            }
        }

        return $proceed($useSid);
    }

    /**
     * Bypass the legacy check for displaying product in category, by returning true for virtual categories.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Model\Product $product    The product
     * @param \Closure                       $proceed    The legacy canBeShowInCategory function
     * @param int                            $categoryId The category id
     *
     * @return bool
     */
    public function aroundCanBeShowInCategory(Product $product, \Closure $proceed, $categoryId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            if ((bool) $category->getIsVirtualCategory() === true) {
                return true;
            }
        } catch (NoSuchEntityException $e) {
            $category = null;
        }

        return $proceed($categoryId);
    }

    /**
     * Retrieve request path for the product and the current category.
     *
     * @param \Magento\Catalog\Model\Product $product The product
     *
     * @return string
     */
    private function getRequestPath($product)
    {
        $requestPath = null;

        if ($product->getCategory()) {
            $requestPath = $this->urlModel->getProductRequestPath($product, $product->getCategory());
        }

        return $requestPath;
    }

    /**
     * Retrieve the currently applied root category, if any.
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    private function getAppliedRootCategory()
    {
        return $this->virtualCategoryRoot->getAppliedRootCategory();
    }
}
