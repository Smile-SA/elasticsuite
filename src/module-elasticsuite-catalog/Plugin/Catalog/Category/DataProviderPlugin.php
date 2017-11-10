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
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;
use Smile\ElasticsuiteCatalog\Model\CoverageProvider;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;

/**
 * Elasticsuite Data Provider Plugin for Category Edit Form.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataProviderPlugin
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributeCollectionFactory;

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
     * @var CoverageProvider
     */
    private $coverageProvider;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $attributes = null;

    /**
     * DataProviderPlugin constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory Attribute Collection Factory.
     * @param FulltextCollectionFactory  $productCollectionFactory   Product Collection Factory.
     * @param ScopeConfigInterface       $scopeConfig                Scope Configuration.
     * @param StoreManagerInterface      $storeManagerInterface      Store Manager.
     * @param CoverageProvider           $coverageProvider           Coverage Provider.
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        FulltextCollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        CoverageProvider $coverageProvider
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productCollectionFactory   = $productCollectionFactory;
        $this->scopeConfig                = $scopeConfig;
        $this->storeManager               = $storeManagerInterface;
        $this->coverageProvider           = $coverageProvider;
    }

    /**
     * Append filter configuration (sort order and display mode) data.
     * Meta is added in the ui_component via XML.
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

        $data[$currentCategory->getId()]['layered_navigation_filters'] = $this->getFilterableAttributeList($currentCategory);

        return $data;
    }

    /**
     * Retrieve facet configuration for current category.
     * Compute the intersection between existing data for the category, and all attributes set as filterable.
     *
     * @param CategoryInterface $currentCategory Current Category
     *
     * @return array
     */
    private function getFilterableAttributeList($currentCategory)
    {
        $configuration      = [];
        $relevantAttributes = $this->getRelevantAttributes($currentCategory);

        foreach ($this->getAttributes($currentCategory) as $attribute) {
            $isRelevant    = in_array($attribute->getAttributeCode(), array_keys($relevantAttributes));
            $productNumber = $isRelevant ? $relevantAttributes[$attribute->getAttributeCode()]['count'] : 0;

            $configuration[] = [
                'attribute_id'    => $attribute->getAttributeId(),
                'attribute_label' => $attribute->getFrontendLabel(),
                'position'        => $attribute->getPosition() ? $attribute->getPosition() : PHP_INT_MAX,
                'display_mode'    => $attribute->hasDisplayMode() ? $attribute->getDisplayMode() : DisplayMode::AUTO_DISPLAYED,
                'relevant'        => $isRelevant,
                'product_match'   => $productNumber,
            ];
        }

        return $configuration;
    }

    /**
     * Retrieve attribute collection pre-filtered with only attribute filterable.
     *
     * @param CategoryInterface $category Category
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface[]
     */
    private function getAttributes(CategoryInterface $category)
    {
        $extensionAttributes = $category->getExtensionAttributes();
        if (null !== $extensionAttributes && $category->getId()) {
            $this->attributes = $extensionAttributes->getFilterableAttributeList();
        }

        if ($this->attributes === null) {
            $collection = $this->attributeCollectionFactory->create(['category' => $category]);
            $collection
                ->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->addIsFilterableFilter()
                ->addStoreLabel($category->getStoreId())
                ->setOrder('position', 'ASC');

            $this->attributes = $collection->getItems();
        }

        return $this->attributes;
    }

    /**
     * Retrieve only the "relevant" attributes : they are the attributes which are actually
     * matching products in the category.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return array
     */
    private function getRelevantAttributes(CategoryInterface $category)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($this->getStoreId($category))
            ->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);

        if ($this->isEnabledShowOutOfStock($this->getStoreId($category))) {
            $collection->addIsInStockFilter();
        }

        if ($category->getVirtualRule()) { // Implicit dependency to Virtual Categories module.
            $collection->addQueryFilter($category->getVirtualRule()->getCategorySearchQuery($category));
        } elseif (!$category->getVirtualRule()) {
            $collection->addCategoryFilter($category);
        }

        $coverage = $this->coverageProvider->getAttributesCoverage($collection);

        return array_filter(
            $coverage,
            function ($item) {
                return (int) $item['count'] > 0;
            }
        );
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

        return $storeId;
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
