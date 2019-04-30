<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position as ProductPositionResource;
use Magento\Catalog\Model\Category;

/**
 * Extenstion of the category form UI data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProviderPlugin
{
    /**
     *
     * @var \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position
     */
    private $productPositionResource;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param ProductPositionResource                   $productPositionResource Product position resource model.
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat            Locale formater.
     * @param \Magento\Backend\Model\UrlInterface       $urlBuilder              Admin URL Builder.
     * @param StoreManagerInterface                     $storeManagerInterface   Store Manager.
     */
    public function __construct(
        ProductPositionResource $productPositionResource,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->productPositionResource = $productPositionResource;
        $this->localeFormat            = $localeFormat;
        $this->urlBuilder              = $urlBuilder;
        $this->storeManager            = $storeManagerInterface;
    }

    /**
     * Append virtual rule and sorting product data.
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param \Closure             $proceed      Original method.
     *
     * @return array
     */
    public function aroundGetData(CategoryDataProvider $dataProvider, \Closure $proceed)
    {
        $data = $proceed();

        $currentCategory = $dataProvider->getCurrentCategory();

        if ($currentCategory->getId() === null || $currentCategory->getLevel() < 2) {
            $data[$currentCategory->getId()]['use_default']['is_virtual_category'] = true;
        }

        if ($currentCategory->getLevel() >= 2 && !isset($data[$currentCategory->getId()]['virtual_category_root'])) {
            $data[$currentCategory->getId()]['virtual_category_root'] = $currentCategory->getPathIds()[1];
        }

        $data[$currentCategory->getId()]['use_default']['show_use_store_positions'] = true;
        if (!$currentCategory->getStoreId() || $currentCategory->getId() === null) {
            $data[$currentCategory->getId()]['use_default']['show_use_store_positions'] = false;
        }

        // To restore global/"All store views" positions/blacklist.
        $data[$currentCategory->getId()]['default']['sorted_products'] = [];
        $data[$currentCategory->getId()]['default']['blacklisted_products'] = [];
        if ($currentCategory->getStoreId()) {
            $globalCategory = clone $currentCategory;
            $globalCategory->setUseStorePositions(false);
            $data[$currentCategory->getId()]['default']['sorted_products'] = $this->getProductSavedPositions($globalCategory);
            $data[$currentCategory->getId()]['default']['blacklisted_products'] = $this->getBlacklistedProducts($globalCategory);
        }

        $data[$currentCategory->getId()]['sorted_products']         = $this->getProductSavedPositions($currentCategory);
        $data[$currentCategory->getId()]['blacklisted_products']    = $this->getBlacklistedProducts($currentCategory);
        $data[$currentCategory->getId()]['product_sorter_load_url'] = $this->getProductSorterLoadUrl($currentCategory);
        $data[$currentCategory->getId()]['price_format']            = $this->localeFormat->getPriceFormat();

        return $data;
    }

    /**
     * Retrieve the category product sorter load URL.
     *
     * @param Category $category Category.
     *
     * @return string|null
     */
    private function getProductSorterLoadUrl(Category $category)
    {
        $url = null;

        $storeId = $this->getStoreId($category);

        if ($storeId) {
            $urlParams = ['ajax' => true, 'store' => $storeId];
            $url = $this->urlBuilder->getUrl('virtualcategory/category_virtual/preview', $urlParams);
        }

        return $url;
    }

    /**
     * Load product saved positions for the current category.
     *
     * @param Category $category Category.
     *
     * @return array
     */
    private function getProductSavedPositions(Category $category)
    {
        $productPositions = [];

        if ($category->getId()) {
            $productPositions = $this->productPositionResource->getProductPositionsByCategory($category);
        }

        return json_encode($productPositions, JSON_FORCE_OBJECT);
    }

    /**
     * Return list of blacklisted products for the current category.
     *
     * @param Category $category Category.
     *
     * @return array
     */
    private function getBlacklistedProducts(Category $category)
    {
        $productIds = $this->productPositionResource->getProductBlacklistByCategory($category);

        return array_map('intval', $productIds);
    }

    /**
     * Retrieve default store view id.
     *
     * @return int
     */
    private function getDefaultStoreId()
    {
        $store = $this->storeManager->getDefaultStoreView();

        if (null === $store) {
            // Occurs when current user does not have access to default website (due to AdminGWS ACLS on Magento EE).
            $store = !empty($this->storeManager->getWebsites()) ? current($this->storeManager->getWebsites())->getDefaultStore() : null;
        }

        return $store ? $store->getId() : 0;
    }

    /**
     * Get store id for the current category.
     *
     *
     * @param Category $category Category.
     *
     * @return int
     */
    private function getStoreId(Category $category)
    {
        $storeId = $category->getStoreId();

        if ($storeId === 0) {
            $defaultStoreId   = $this->getDefaultStoreId();
            $categoryStoreIds = array_filter($category->getStoreIds());
            $storeId        = current($categoryStoreIds);
            if (in_array($defaultStoreId, $categoryStoreIds)) {
                $storeId = $defaultStoreId;
            }
        }

        return $storeId;
    }
}
