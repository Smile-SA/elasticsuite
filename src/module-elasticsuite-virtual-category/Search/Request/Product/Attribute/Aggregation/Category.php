<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Search\Request\Product\Attribute\Aggregation;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Category Aggregation Builder
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Category implements AggregationInterface
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $context;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $contextInterface   Search Context
     * @param \Magento\Framework\App\CacheInterface               $cache              Cache
     * @param \Magento\Store\Model\StoreManagerInterface          $storeManager       Store Manager
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface    $categoryRepository Category Repository
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $contextInterface,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        $this->cache              = $cache;
        $this->context            = $contextInterface;
        $this->storeManager       = $storeManager;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregationData(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $facetQueries = $this->getFacetQueries();
        $bucketConfig = ['type' => BucketInterface::TYPE_QUERY_GROUP, 'name' => 'categories', 'queries' => $facetQueries];

        return $bucketConfig;
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
     * Load data from the cache if exits. Use a callback on the current category virtual root if not yet present into the cache.
     *
     * @param string $callback name of the virtual rule method to be used for actual loading.
     *
     * @return mixed
     */
    private function loadUsingCache($callback)
    {
        $category = $this->getCurrentCategory();
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

    /**
     * Retrieve current category from search context or instantiate the default root of current store.
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    private function getCurrentCategory()
    {
        $category = $this->context->getCurrentCategory();

        if (null === $category) {
            $category = $this->categoryRepository->get(
                $this->storeManager->getStore()->getRootCategoryId(),
                $this->storeManager->getStore()->getId()
            );
        }

        return $category;
    }
}
