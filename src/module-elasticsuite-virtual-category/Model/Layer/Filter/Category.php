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

namespace Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Product category filter implementation using virtual categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Category
{
    /**
     *
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Url
     */
    private $urlModel;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                  $filterItemFactory   Filter item factory.
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                     $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder             $itemDataBuilder     Item data builder.
     * @param \Magento\Framework\Escaper                                       $escaper             HTML escaper.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory Data provider.
     * @param \Magento\Framework\App\CacheInterface                            $cache               Cache.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Url                     $urlModel            Virtual Categories URL Model
     * @param boolean                                                          $useUrlRewrites      Uses URLs rewrite for rendering.
     * @param array                                                            $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Smile\ElasticsuiteVirtualCategory\Model\Url $urlModel,
        $useUrlRewrites = false,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $dataProviderFactory,
            $useUrlRewrites,
            $data
        );
        $this->urlModel = $urlModel;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection($config = [])
    {
        $facetQueries = $this->getFacetQueries();

        $facetType   = BucketInterface::TYPE_QUERY_GROUP;
        $facetField  = $this->getFilterField();
        $facetConfig = ['name' => $facetField, 'queries' => $facetQueries];

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, $facetConfig);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilterField()
    {
        return 'categories';
    }

    /**
     * {@inheritDoc}
     */
    protected function applyCategoryFilterToCollection(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $query = $this->getFilterQuery();

        if ($query !== null) {
            $this->getLayer()->getProductCollection()->addQueryFilter($query);
        }

        return $this;
    }

    /**
     * Retrieve currently selected category children categories.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|\Magento\Catalog\Model\Category[]
     */
    protected function getChildrenCategories()
    {
        if ($this->childrenCategories === null) {
            $currentCategory = $this->getDataProvider()->getCategory();
            // Use the root category to retrieve children if needed.
            if ($this->useVirtualRootCategorySubtree($currentCategory)) {
                $currentCategory = $this->getVirtualRootCategory($currentCategory);
            }
            $this->childrenCategories = $currentCategory->getChildrenCategories();
        }

        return $this->childrenCategories;
    }

    /**
     * Retrieve Category Url to build filter
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $childCategory Category.
     *
     * @return string
     */
    protected function getCategoryFilterUrl($childCategory)
    {
        $url = parent::getCategoryFilterUrl($childCategory);

        $currentCategory = $this->getDataProvider()->getCategory();

        $appliedRootCategory = $this->getDataProvider()->getAppliedRootCategory();

        // Use the root category to retrieve children categories Url if needed.
        if ($this->useVirtualRootCategorySubtree($currentCategory)) {
            $url = $this->urlModel->getVirtualCategorySubtreeUrl($currentCategory, $childCategory);
        } elseif ($appliedRootCategory) {
            // Occurs when navigating through the subtree of a virtual root category.
            $url = $this->urlModel->getVirtualCategorySubtreeUrl($appliedRootCategory, $childCategory);
        }

        return $url;
    }

    /**
     * Retrieve the Virtual Root Category of a category.
     *
     * @param CategoryInterface $category The category
     *
     * @return CategoryInterface
     */
    private function getVirtualRootCategory($category)
    {
        $virtualRule  = $category->getVirtualRule();
        $rootCategory = $virtualRule->getVirtualRootCategory($category);

        return $rootCategory;
    }

    /**
     * Check if a category is configured to use its "virtual root category" to display facets
     *
     * @param CategoryInterface $category The category
     *
     * @return bool
     */
    private function useVirtualRootCategorySubtree($category)
    {
        $rootCategory = $this->getVirtualRootCategory($category);

        return ($rootCategory && $rootCategory->getId() && (bool) $category->getGenerateRootCategorySubtree());
    }

    /**
     * List of subcategories queries by category id.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    private function getFacetQueries()
    {
        $category = $this->getDataProvider()->getCategory();

        // Use the root category to display facets if configured this way.
        if ($this->useVirtualRootCategorySubtree($category) && $this->useUrlRewrites()) {
            $category = $this->getVirtualRootCategory($category);
        }

        return $this->loadUsingCache($category, 'getSearchQueriesByChildren');
    }

    /**
     * Current category filter query.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getFilterQuery()
    {
        $category            = $this->getDataProvider()->getCategory();
        $appliedRootCategory = $this->getDataProvider()->getAppliedRootCategory();
        $categoryFilter      = $this->loadUsingCache($category, 'getCategorySearchQuery');

        if ($appliedRootCategory && $appliedRootCategory->getId()) {
            $categoryFilter = $category->getVirtualRule()->mergeCategoryQueries([$category, $appliedRootCategory]);
        }

        return $categoryFilter;
    }

    /**
     * Load data from the cache if exits. Use a callback on the current category virtual root if not yet present into the cache.
     *
     * @param CategoryInterface $category The category
     * @param string            $callback name of the virtual rule method to be used for actual loading.
     *
     * @return mixed
     */
    private function loadUsingCache($category, $callback)
    {
        $cacheKey = implode('|', [$callback, $category->getStoreId(), $category->getId()]);

        $data = $this->cache->load($cacheKey);

        if ($data !== false) {
            $data = unserialize($data);
        }

        if ($data === false) {
            $virtualRule = $category->getVirtualRule();
            $data = call_user_func_array([$virtualRule, $callback], [$category]);
            $cacheData = serialize($data);
            $this->cache->save($cacheData, $cacheKey, [\Magento\Catalog\Model\Category::CACHE_TAG]);
        }

        return $data;
    }
}
