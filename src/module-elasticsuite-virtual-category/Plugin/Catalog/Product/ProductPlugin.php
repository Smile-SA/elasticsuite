<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Product;

use Closure as ClosureAlias;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticsuiteVirtualCategory\Model\Url;

/**
 * Product Plugin for Virtual Categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
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
     * @var RequestInterface
     */
    private $request;

    /**
     * ProductPlugin constructor.
     *
     * @param Url                         $urlModel           Virtual Categories URL Model
     * @param CategoryRepositoryInterface $categoryRepository Category Repository
     * @param RequestInterface            $request            Request
     */
    public function __construct(Url $urlModel, CategoryRepositoryInterface $categoryRepository, RequestInterface $request)
    {
        $this->urlModel = $urlModel;
        $this->categoryRepository = $categoryRepository;
        $this->request = $request;
    }

    /**
     * Append virtual category Url to product request path, if needed.
     *
     * @param \Magento\Catalog\Model\Product $product The Product
     * @param ClosureAlias                   $proceed The legacy getProductUrl method
     * @param null                           $useSid  If the SID should be used or not
     *
     * @return mixed
     */
    public function aroundGetProductUrl(Product $product, ClosureAlias $proceed, $useSid = null)
    {
        $requestPath = $product->getRequestPath();
        if (empty($requestPath)) {
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
     * @param ClosureAlias                   $proceed    The legacy canBeShowInCategory function
     * @param int                            $categoryId The category id
     *
     * @return bool
     */
    public function aroundCanBeShowInCategory(Product $product, ClosureAlias $proceed, $categoryId): bool
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
    private function getRequestPath($product): ?string
    {
        $requestPath = null;
        try {
            $category = $this->categoryRepository->get((int) $this->request->getParam('id', false));

            if ($category) {
                $requestPath = $this->urlModel->getProductRequestPath($product, $category);
            }
        } catch (NoSuchEntityException $e) {
            $requestPath = null;
        }

        return $requestPath;
    }
}
