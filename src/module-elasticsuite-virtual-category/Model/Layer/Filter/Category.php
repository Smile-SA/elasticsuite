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
     * List of subcategories queries by category id.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface[]
     */
    private function getFacetQueries()
    {
        return $this->loadUsingCache('getSearchQueriesByChildren');
    }

    /**
     * Current category filter query.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getFilterQuery()
    {
        return $this->loadUsingCache('getCategorySearchQuery');
    }

    /**
     * Load data from the cache if exits. Use a callback on the current category virtual root if not yet present into the cache.
     *
     * @param string $callback name of the virtual rule method to be used for actual loading.
     *
     * @return mixed
     */
    private function loadUsingCache($callback)
    {
        $category = $this->getDataProvider()->getCategory();
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
