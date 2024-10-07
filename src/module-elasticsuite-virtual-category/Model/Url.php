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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory as CategoryCollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Url Model for Virtual Categories
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Url
{
    /**
     * XML path for product url suffix
     */
    public const XML_PATH_PRODUCT_URL_SUFFIX = 'catalog/seo/product_url_suffix';

    /**
     * XML path for product url suffix
     */
    const XML_PATH_CATEGORY_URL_SUFFIX = 'catalog/seo/category_url_suffix';

    /**
     * Store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * ProductPlugin constructor.
     *
     * @param ScopeConfigInterface      $scopeConfig               Scope Configuration
     * @param StoreManagerInterface     $storeManager              Store Manager Interface
     * @param CategoryCollectionFactory $categoryCollectionFactory Category Collection Factory
     * @param UrlFinderInterface        $urlFinder                 URL Finder
     * @param UrlInterface              $urlBuilder                URL Builder
     * @param VirtualCategoryRoot       $virtualCategoryRoot       Virtual Category Root model
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        UrlFinderInterface $urlFinder,
        UrlInterface $urlBuilder,
        VirtualCategoryRoot $virtualCategoryRoot
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlFinder = $urlFinder;
        $this->urlBuilder = $urlBuilder;
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * Retrieve Url for a given product/category couple
     *
     * @param Product  $product  The Product
     * @param Category $category The Category
     * @param array    $params   Additional Url Params, if needed
     *
     * @return string
     */
    public function getProductUrl($product, $category, $params = null): string
    {
        return trim($this->urlBuilder->getUrl($this->getProductRequestPath($product, $category), $params), '/');
    }

    /**
     * Retrieve rewrite object for a given product/category request path couple.
     * Will only succeed if the category path is related to a virtual one.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param string   $productRequestPath  A product Request Path
     * @param string   $categoryRequestPath A category Request Path
     * @param int|null $storeId             Store Id
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    public function getProductRewrite($productRequestPath, $categoryRequestPath, $storeId = null): ?UrlRewrite
    {
        $productRewrite = null;
        if (null === $storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        if (!$this->virtualCategoryRoot->getAppliedRootCategory()) {
            $categoryId = $this->getVirtualCategoryIdByPath($categoryRequestPath);
        } else {
            $urlKeys = explode('/', $categoryRequestPath);
            $urlKey  = array_pop($urlKeys);
            $category = $this->loadCategoryByUrlKey($urlKey);
            $categoryId = $category->getId();
        }
        if ($categoryId) {
            $productRewrite = $this->urlFinder->findOneByData([
                UrlRewrite::REQUEST_PATH => trim($productRequestPath, '/') . ($this->getProductUrlSuffix() === '/' ? '/' : ''),
                UrlRewrite::STORE_ID => $storeId,
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
     * @param Product  $product  The product
     * @param Category $category The category
     *
     * @return string
     */
    public function getProductRequestPath($product, $category): ?string
    {
        $requestPath     = null;
        $categoryUrlPath = null;
        $productUrlKey   = $product->getUrlKey();

        if ($this->isVirtualCategory($category) && $this->useCategoryPath()) {
            $categoryUrlPath = $category->getUrlPath();
        } elseif ($this->virtualCategoryRoot->getAppliedRootCategory() && $this->useCategoryPath()) {
            $categoryUrlPath = $this->virtualCategoryRoot->getVirtualCategorySubtreePath(
                $this->virtualCategoryRoot->getAppliedRootCategory(),
                $category
            );
        }

        if ($categoryUrlPath && $productUrlKey) {
            $requestPath = $categoryUrlPath . '/' . $productUrlKey . $this->getProductUrlSuffix();
        }

        return $requestPath;
    }

    /**
     * Build an url for a category being viewed under the subtree of a virtual category
     *
     * @param CategoryInterface $appliedRootCategory The applied root category
     * @param CategoryInterface $childCategory       The child category to retrieve Url for.
     *
     * @return string
     */
    public function getVirtualCategorySubtreeUrl($appliedRootCategory, $childCategory)
    {
        $path = $this->virtualCategoryRoot->getVirtualCategorySubtreePath($appliedRootCategory, $childCategory);
        $url  = $path . $this->getCategoryUrlSuffix();

        $baseUrl = $childCategory->getUrlInstance()->getBaseUrl();

        return $baseUrl . $url;
    }

    /**
     * Retrieve Category Url Rewrite by path and Store.
     *
     * @param string $categoryPath A category Path
     * @param int    $storeId      The Store Id
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    public function getCategoryRewrite($categoryPath, $storeId)
    {
        $categoryPath = str_replace($this->getCategoryUrlSuffix() ?? '', '', $categoryPath);
        $category = $this->loadCategoryByUrlKey($categoryPath);
        $rewrite  = null;

        if ($category && $category->getId()) {
            $rewrite = $this->urlFinder->findOneByData([
                UrlRewrite::ENTITY_ID   => $category->getId(),
                UrlRewrite::STORE_ID    => $storeId,
                UrlRewrite::ENTITY_TYPE => 'category',
            ]);

            if ($rewrite) {
                $rewrite->setRequestPath($rewrite->getTargetPath());
            }
        }

        return $rewrite;
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
     * Load a category by Url key.
     *
     * @param string $requestPath The Request Path
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadCategoryByUrlKey($requestPath)
    {
        $collection = $this->categoryCollectionFactory->create();

        $collection->addIsActiveFilter()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->addAttributeToFilter('url_key', ['eq' => $requestPath]);

        return $collection->getFirstItem();
    }

    /**
     * Load a virtual category by request path.
     *
     * @param string $requestPath The Request Path
     *
     * @return DataObject
     * @throws LocalizedException
     */
    private function loadVirtualCategoryByUrlPath($requestPath): DataObject
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addIsActiveFilter()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->addAttributeToFilter('url_path', ['eq' => $requestPath])
            ->addAttributeToFilter('is_virtual_category', ['eq' => 1]);

        return $collection->getFirstItem();
    }

    /**
     * Check if a category is virtual.
     *
     * @param Category $category The category
     *
     * @return bool
     */
    private function isVirtualCategory($category): bool
    {
        return (bool) $category->getIsVirtualCategory();
    }

    /**
     * Check if urls should be rendered by including the category path.
     *
     * @return bool
     */
    private function useCategoryPath(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            \Magento\Catalog\Helper\Product::XML_PATH_PRODUCT_URL_USE_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve Product Url suffix
     *
     * @return string
     */
    private function getProductUrlSuffix()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_URL_SUFFIX, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve Category Url suffix
     *
     * @return string
     */
    private function getCategoryUrlSuffix()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_URL_SUFFIX, ScopeInterface::SCOPE_STORE);
    }
}
