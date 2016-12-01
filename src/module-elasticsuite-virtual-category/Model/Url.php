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
namespace Smile\ElasticsuiteVirtualCategory\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Url Model for Virtual Categories
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Url
{
    /**
     * XML path for product url suffix
     */
    const XML_PATH_PRODUCT_URL_SUFFIX = 'catalog/seo/product_url_suffix';

    /**
     * Store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\UrlRewrite\Model\UrlFinderInterface
     */
    private $urlFinder;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * ProductPlugin constructor.
     *
     * @param ScopeConfigInterface        $scopeConfig               Scope Configuration
     * @param CategoryRepositoryInterface $categoryRepository        Category Repository
     * @param StoreManagerInterface       $storeManager              Store Manager Interface
     * @param CategoryCollectionFactory   $categoryCollectionFactory Category Collection Factory
     * @param UrlFinderInterface          $urlFinder                 URL Finder
     * @param UrlInterface                $urlInterface              URL Interface
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        UrlFinderInterface $urlFinder,
        UrlInterface $urlInterface
    ) {
        $this->scopeConfig               = $scopeConfig;
        $this->categoryRepository        = $categoryRepository;
        $this->storeManager              = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlFinder                 = $urlFinder;
        $this->urlBuilder                = $urlInterface;
    }

    /**
     * Retrieve Url for a given product/category couple
     *
     * @param \Magento\Catalog\Model\Product  $product  A Product
     * @param \Magento\Catalog\Model\Category $category A Category
     * @param array                           $params   Additional Url Params, if needed
     *
     * @return string
     */
    public function getProductUrl($product, $category, $params = null)
    {
        return trim($this->urlBuilder->getUrl($this->getProductRequestPath($product, $category), $params), '/');
    }

    /**
     * Retrieve rewrite object for a given product/category request path couple.
     * Will only succeed if the category path is related to a virtual one.
     *
     * @param string   $productRequestPath  A product Request Path
     * @param string   $categoryRequestPath A category Request Path
     * @param int|null $storeId             Store Id
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    public function getProductRewrite($productRequestPath, $categoryRequestPath, $storeId = null)
    {
        $productRewrite = null;

        if (null === $storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $categoryId = $this->getVirtualCategoryIdByPath($categoryRequestPath);
        if ($categoryId) {
            $productRewrite = $this->urlFinder->findOneByData([
                UrlRewrite::REQUEST_PATH => trim($productRequestPath, '/'),
                UrlRewrite::STORE_ID     => $storeId,
            ]);

            if (null !== $productRewrite) {
                $targetPath = $productRewrite->getTargetPath();
                $targetPath .= "/category/{$categoryId}";
                $productRewrite->setTargetPath($targetPath);
            }
        }

        return $productRewrite;
    }

    /**
     * Retrieve request path for the product and a virtual category.
     *
     * @param \Magento\Catalog\Model\Product  $product  The product
     * @param \Magento\Catalog\Model\Category $category The product
     *
     * @return string
     */
    public function getProductRequestPath($product, $category)
    {
        $requestPath = null;

        if ($this->isVirtualCategory($category) && $this->useCategoryPath()) {
            $categoryUrlPath = $category->getUrlPath();
            $productUrlKey = $product->getUrlKey();
            if ($productUrlKey) {
                $suffix = $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_URL_SUFFIX, ScopeInterface::SCOPE_STORE);
                $requestPath = $categoryUrlPath . '/' . $productUrlKey . $suffix;
            }
        }

        return $requestPath;
    }

    /**
     * Retrieve a virtual Category Id by its path
     *
     * @param string $categoryPath The category Path
     *
     * @return int|bool
     */
    private function getVirtualCategoryIdByPath($categoryPath)
    {
        $category = $this->loadVirtualCategoryByUrlPath($categoryPath);
        if ($category->getId()) {
            return $category->getId();
        }

        return false;
    }

    /**
     * Load a virtual category by request path.
     *
     * @param string $requestPath The Request Path
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadVirtualCategoryByUrlPath($requestPath)
    {
        $collection = $this->categoryCollectionFactory->create();

        $collection->setStoreId($this->storeManager->getStore()->getId())
            ->addAttributeToFilter('url_path', ['eq' => $requestPath])
            ->addAttributeToFilter('is_virtual_category', ['eq' => 1]);

        return $collection->getFirstItem();
    }

    /**
     * Check if a category is virtual.
     *
     * @param \Magento\Catalog\Model\Category $category The category
     *
     * @return bool
     */
    private function isVirtualCategory($category)
    {
        return (bool) $category->getIsVirtualCategory();
    }

    /**
     * Check if urls should be rendered by including the category path.
     *
     * @return bool
     */
    private function useCategoryPath()
    {
        return (bool) $this->scopeConfig->getValue(
            \Magento\Catalog\Helper\Product::XML_PATH_PRODUCT_URL_USE_CATEGORY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
