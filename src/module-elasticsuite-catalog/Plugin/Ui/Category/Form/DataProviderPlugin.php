<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Ui\Category\Form;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterDisplayMode;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider as CategoryFilterProvider;
use Magento\Catalog\Model\Category\DataProvider as CategoryDataProvider;

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
     * @var array
     */
    protected $elementsWithUseConfigSetting = [
        'sort_direction',
    ];

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var FulltextCollectionFactory
     */
    private $fulltextCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider
     */
    private $filterProvider;

    /**
     * DataProviderPlugin constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory Attribute Collection Factory.
     * @param FulltextCollectionFactory  $fulltextCollectionFactory  Fulltext Collection Factory.
     * @param StoreManagerInterface      $storeManager               Store Manager.
     * @param ContextInterface           $searchContext              Search context.
     * @param CategoryFilterProvider     $categoryFilterProvider     Category Filter Provider.
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        FulltextCollectionFactory $fulltextCollectionFactory,
        StoreManagerInterface $storeManager,
        ContextInterface $searchContext,
        CategoryFilterProvider $categoryFilterProvider
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->fulltextCollectionFactory  = $fulltextCollectionFactory;
        $this->storeManager               = $storeManager;
        $this->searchContext              = $searchContext;
        $this->filterProvider             = $categoryFilterProvider;
    }

    /**
     * Remove filter configuration from meta in case of a new category, or a root one.
     * Meta is added in the ui_component via XML.
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param \Closure             $proceed      Original method.
     *
     * @return array
     */
    public function aroundGetMeta(CategoryDataProvider $dataProvider, \Closure $proceed)
    {
        $meta = $proceed();

        $currentCategory = $dataProvider->getCurrentCategory();

        if ($currentCategory->getId() === null || $currentCategory->getLevel() < 2) {
            $meta['display_settings']['children']['facet_config']['arguments']['data']['config']['visible'] = false;
            $meta['display_settings']['children']['facet_config']['arguments']['data']['config']['componentType'] = 'field';
            $meta['display_settings']['children']['facet_config']['arguments']['data']['config']['formElement'] = 'input';
        }

        return $meta;
    }

    /**
     * Append filter configuration (sort order and display mode) data.
     * Meta is added in the ui_component via XML.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
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

        if ($currentCategory->getId() !== null && $currentCategory->getLevel() >= 2) {
            $data[$currentCategory->getId()]['facet_config'] = $this->getFilterableAttributeList($currentCategory);
            $categoryData = &$data[$currentCategory->getId()];

            foreach ($this->elementsWithUseConfigSetting as $elementsWithUseConfigSetting) {
                if (!isset($categoryData['use_config'][$elementsWithUseConfigSetting])) {
                    if (!isset($categoryData[$elementsWithUseConfigSetting]) ||
                        ($categoryData[$elementsWithUseConfigSetting] == '')
                    ) {
                        $categoryData['use_config'][$elementsWithUseConfigSetting] = true;
                    } else {
                        $categoryData['use_config'][$elementsWithUseConfigSetting] = false;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Modify default metadata to include 'use_config.sort_direction'.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param CategoryDataProvider $dataProvider Data provider.
     * @param array                $result       Original data.
     *
     * @return array
     */
    public function afterGetDefaultMetaData(CategoryDataProvider $dataProvider, array $result)
    {
        $result['use_config.sort_direction']['default'] = true;

        return $result;
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
        $attributesList = [];

        foreach ($this->getAttributes($currentCategory) as $attribute) {
            $attributesList[] = [
                'attribute_id'                        => (int) $attribute->getAttributeId(),
                'attribute_label'                     => $attribute->getFrontendLabel(),
                'facet_display_mode'                  => (int) ($attribute->getFacetDisplayMode() ?? FilterDisplayMode::AUTO_DISPLAYED),
                'facet_min_coverage_rate'             => $attribute->getFacetMinCoverageRate(),
                'use_default_facet_min_coverage_rate' => (bool) $attribute->getUseDefaultFacetMinCoverageRate(),
                'facet_max_size'                      => $attribute->getFacetMaxSize(),
                'use_default_facet_max_size'          => (bool) $attribute->getUseDefaultFacetMaxSize(),
                'facet_sort_order'                    => $attribute->getFacetSortOrder(),
                'use_default_facet_sort_order'        => (bool) $attribute->getUseDefaultFacetSortOrder(),
                'is_pinned'                           => !(bool) $attribute->getUseDefaultPosition(),
                'default_position'                    => (int) $attribute->getDefaultPosition(),
                'position'                            => (int) $attribute->getPosition(),
            ];
        }

        return $attributesList;
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
     * @param CategoryInterface $category Category.
     *
     * @return int
     */
    private function getStoreId(CategoryInterface $category)
    {
        $storeId = $category->getStoreId();

        if ($storeId === 0) {
            $defaultStoreId   = $this->getDefaultStoreId();
            $categoryStoreIds = array_filter($category->getStoreIds());
            $storeId          = current($categoryStoreIds);
            if (in_array($defaultStoreId, $categoryStoreIds)) {
                $storeId = $defaultStoreId;
            }
        }

        return $storeId;
    }

    /**
     * Return category filter param
     *
     * @param CategoryInterface $category Category.
     *
     * @return int|QueryInterface
     */
    private function getCategoryFilterParam(CategoryInterface $category)
    {
        return $this->filterProvider->getQueryFilter($category);
    }

    /**
     * Retrieve attribute collection pre-filtered with only filterable attributes.
     *
     * @param CategoryInterface $category Category
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface[]
     */
    private function getAttributes($category)
    {
        $collection = $this->attributeCollectionFactory->create(['category' => $category]);
        $collection
            ->setCategory($category)
            ->addIsFilterableFilter()
            ->addStoreLabel($category->getStoreId())
            ->setOrder('position', 'ASC')
            ->setOrder('attribute_id', 'ASC');

        $storeId = $this->getStoreId($category);

        if ($storeId && $category->getId()) {
            // Make a side effect on the context that will be used by the fulltext collection's request builder.
            $this->searchContext->setCurrentCategory($category)
                ->setStoreId($storeId);

            /** @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $fulltextCollection */
            $fulltextCollection = $this->fulltextCollectionFactory->create();
            $fulltextCollection->setStoreId($storeId)
                ->setPageSize(0)
                ->addFieldToFilter('category_ids', $this->getCategoryFilterParam($category));

            $indexedAttributes = array_keys($fulltextCollection->getFacetedData('indexed_attributes'));
            if (!empty($indexedAttributes)) {
                $collection->setCodeFilter($indexedAttributes);
            }
        }

        return $collection->getItems();
    }
}
