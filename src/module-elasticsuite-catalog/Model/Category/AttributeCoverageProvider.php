<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\CoverageProviderFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;

/**
 * Attribute Coverage Provider for a category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributeCoverageProvider
{
    /**
     * @var \Magento\Catalog\Api\Data\CategoryInterface
     */
    private $category;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CoverageProviderFactory
     */
    private $coverageProviderFactory;

    /**
     * Category Coverage Provider Constructor
     *
     * @param CategoryInterface         $category                 Category
     * @param FulltextCollectionFactory $productCollectionFactory Product Collection Factory.
     * @param ScopeConfigInterface      $scopeConfig              Scope Configuration.
     * @param StoreManagerInterface     $storeManagerInterface    Store Manager.
     * @param CoverageProviderFactory   $coverageProviderFactory  Coverage Provider Factory.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        CoverageProviderFactory $coverageProviderFactory
    ) {
        $this->category                 = $category;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig              = $scopeConfig;
        $this->storeManager             = $storeManagerInterface;
        $this->coverageProviderFactory  = $coverageProviderFactory;
    }

    /**
     * Retrieve attributes coverage for a given category.
     *
     * @return array
     */
    public function getAttributesCoverage()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($this->getStoreId($this->category))
            ->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);

        if ($this->isEnabledShowOutOfStock($this->getStoreId($this->category))) {
            $collection->addIsInStockFilter();
        }

        if ($this->category->getVirtualRule()) { // Implicit dependency to Virtual Categories module.
            $collection->addQueryFilter($this->category->getVirtualRule()->getCategorySearchQuery($this->category));
        } elseif (!$this->category->getVirtualRule()) {
            $collection->addCategoryFilter($this->category);
        }

        $coverageProvider = $this->coverageProviderFactory->create(['collection' => $collection]);

        return $coverageProvider->getAttributesCoverage();
    }

    /**
     * Retrieve Store Id from current category, or default to the default storeview.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return int
     */
    private function getStoreId(CategoryInterface $category)
    {
        $defaultStoreId = $this->getDefaultStoreView()->getId();
        $storeId        = current(array_filter($category->getStoreIds()));
        if (in_array($defaultStoreId, $category->getStoreIds())) {
            $storeId = $defaultStoreId;
        }

        return $storeId ? $storeId : $defaultStoreId;
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

    /**
     * Get config value for 'display out of stock' option
     *
     * @param int $storeId The Store Id
     *
     * @return bool
     */
    private function isEnabledShowOutOfStock($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            'cataloginventory/options/show_out_of_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
