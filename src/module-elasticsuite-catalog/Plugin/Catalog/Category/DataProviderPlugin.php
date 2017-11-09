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
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

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
     * @var FulltextCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $attributes = null;

    /**
     * DataProviderPlugin constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory Attribute Collection Factory.
     * @param FulltextCollectionFactory  $productCollectionFactory   Product Collection Factory.
     * @param QueryFactory               $queryFactory               Query Factory.
     * @param ScopeConfigInterface       $scopeConfig                Scope Configuration.
     * @param StoreManagerInterface      $storeManagerInterface      Store Manager.
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        FulltextCollectionFactory $productCollectionFactory,
        QueryFactory $queryFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productCollectionFactory   = $productCollectionFactory;
        $this->queryFactory               = $queryFactory;
        $this->scopeConfig                = $scopeConfig;
        $this->storeManager               = $storeManagerInterface;
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
            ->addFacet('indexed_attributes', BucketInterface::TYPE_TERM, ['size' => 0])
            ->addQueryFilter($this->getCategorySearchQuery($category))
            ->setPageSize(0);

        $bucket = $collection->getFacetedData('indexed_attributes');

        return array_filter(
            $bucket,
            function ($item) {
                return (int) $item['count'] > 0;
            }
        );
    }

    /**
     * Retrieve query to build to retrieve products of a given category.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getCategorySearchQuery(CategoryInterface $category)
    {
        $filterParams = [
            'category.category_id' => $category->getId(),
            'visibility'           => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH],
        ];

        if (!$this->isEnabledShowOutOfStock($category->getStoreId())) {
            $filterParams['stock.is_in_stock'] = true;
        }

        if ($category->getVirtualRule()) { // Implicit dependency to Virtual Categories module.
            $filterParams['category'] = $category->getVirtualRule()->getCategorySearchQuery($category);
            unset($filterParams['category.category_id']);
        }

        $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $filterParams);

        return $query;
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
