<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
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

        $data[$currentCategory->getId()]['sorted_products'] = $this->getProductSavedPositions($currentCategory);
        $data[$currentCategory->getId()]['product_sorter_load_url'] = $this->getProductSorterLoadUrl($currentCategory);
        $data[$currentCategory->getId()]['price_format'] = $this->localeFormat->getPriceFormat();

        return $data;
    }

    /**
     * Retrieve the category product sorter load URL.
     *
     * @param Category $category Category.
     *
     * @return string
     */
    private function getProductSorterLoadUrl(Category $category)
    {
        $storeId = $category->getStoreId();

        if ($storeId === 0) {
            $defaultStoreId = $this->getDefaultStoreView()->getId();
            $storeId        = current(array_filter($category->getStoreIds()));
            if (in_array($defaultStoreId, $category->getStoreIds())) {
                $storeId = $defaultStoreId;
            }
        }

        $urlParams = ['ajax' => true, 'store' => $storeId];

        return $this->urlBuilder->getUrl('virtualcategory/category_virtual/preview', $urlParams);
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
     * Retrieve default Store View
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    private function getDefaultStoreView()
    {
        $store = $this->storeManager->getDefaultStoreView();
        if (null === $store) {
            // Occurs when current user does not have access to default website (due to AdminGWS ACLS on Magento EE).
            $store = current($this->storeManager->getWebsites())->getDefaultStore();
        }

        return $store;
    }
}
