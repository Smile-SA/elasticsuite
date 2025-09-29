<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Limitation;

use Magento\Search\Model\PopularSearchTerms;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Identities Provider for optimizer limitations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Identities
{
    /**
     * Scope configuration
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Catalog search data
     *
     * @var \Magento\Search\Model\ResourceModel\Query\Collection
     */
    private $queryCollection;

    /**
     * @var OptimizerInterface
     */
    private $optimizer;

    /**
     * Limitation Identities Constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig     Scope Config
     * @param \Magento\Search\Model\ResourceModel\Query\Collection            $queryCollection Search Queries Collection
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer       The Optimizer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Search\Model\ResourceModel\Query\Collection $queryCollection,
        OptimizerInterface $optimizer
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->queryCollection = $queryCollection;
        $this->optimizer       = $optimizer;
    }

    /**
     * Get Limitation identities for the current optimizer.
     *
     * @return array
     */
    public function get()
    {
        $identities = [];
        $origData   = $this->optimizer->getOrigData();
        $containers = $this->optimizer->getData('search_container') ?? [];

        if (!$this->optimizer->isObjectNew()) {
            $containers = array_unique(
                array_keys(array_merge($this->optimizer->getSearchContainers(), $origData['search_containers'] ?? []))
            );
        }

        if (in_array('quick_search_container', $containers)) {
            $identities = array_merge($identities, $this->getSearchQueryIdentities());
        }

        if (in_array('catalog_view_container', $containers)) {
            $identities = array_merge($identities, $this->getCategoryIdentities());
        }

        return $identities;
    }

    /**
     * Get search queries identities related to current optimizer.
     *
     * @return array
     */
    private function getSearchQueryIdentities()
    {
        $identities = [];
        $queryIds   = [];
        $origData   = $this->optimizer->getOrigData();
        $data       = $this->optimizer->getData();

        // If optimizer was previously assigned to all queries, or is now set to all queries.
        $isAppliedToAllQueries = empty($data['quick_search_container'])
            || (bool) $data['quick_search_container']['apply_to'] === false;
        $wasAppliedToAllQueries = empty($origData['quick_search_container']['query_ids']);

        if (!empty($origData['quick_search_container']['query_ids'])) {
            $queryIds = array_merge($queryIds, $origData['quick_search_container']['query_ids']);
        }

        if (!empty($data['quick_search_container']['query_ids'])) {
            foreach ($data['quick_search_container']['query_ids'] as $query) {
                $queryIds[] = $query['id'] ?? $query;
            }
        }

        $queryIds = array_unique(array_filter($queryIds));

        if ($wasAppliedToAllQueries || $isAppliedToAllQueries) {
            $identities[] = \Smile\ElasticsuiteCatalog\Block\CatalogSearch\Result\Cache::POPULAR_SEARCH_CACHE_TAG;
        } elseif (!empty($queryIds)) {
            $popularQueryIds = $this->queryCollection
                ->setPopularQueryFilter($this->optimizer->getStoreId())
                ->setPageSize($this->getMaxCountCacheableSearchTerms($this->optimizer->getStoreId()))
                ->load()
                ->getColumnValues('query_id');

            if (!empty(array_intersect($queryIds, $popularQueryIds))) {
                $identities[] = \Smile\ElasticsuiteCatalog\Block\CatalogSearch\Result\Cache::POPULAR_SEARCH_CACHE_TAG;
            }
        }

        return $identities;
    }

    /**
     * Get category identities related to current optimizer.
     *
     * @return array
     */
    private function getCategoryIdentities()
    {
        $identities  = [];
        $categoryIds = [];
        $origData    = $this->optimizer->getOrigData();
        $data        = $this->optimizer->getData();

        // If optimizer was previously assigned to all categories, or is now set to all categories.
        $isAppliedToAllCategories = empty($data['catalog_view_container'])
            || (bool) $data['catalog_view_container']['apply_to'] === false;

        $wasAppliedToAllCategories = empty($origData['catalog_view_container']['category_ids']);

        // If the optimizer is or was previously assigned to all but some categories.
        $appliedToAllCategoriesButSome = ((int) ($data['catalog_view_container']['apply_to'] ?? 0) === 2)
            || ((int) ($origData['catalog_view_container']['apply_to'] ?? 0) === 2);

        if ($isAppliedToAllCategories || $wasAppliedToAllCategories || $appliedToAllCategoriesButSome) {
            $identities[] = \Magento\Catalog\Model\Category::CACHE_TAG;
        }

        if (!empty($data['catalog_view_container']['category_ids'])) {
            $categoryIds = array_merge($categoryIds, $data['catalog_view_container']['category_ids']);
        }

        if (!empty($origData['catalog_view_container']['category_ids'])) {
            $categoryIds = array_merge($categoryIds, $origData['catalog_view_container']['category_ids']);
        }

        $categoryIds = array_filter(array_unique($categoryIds));
        if (!empty($categoryIds)) {
            $categoryTags = array_map(function ($categoryId) {
                return \Magento\Catalog\Model\Category::CACHE_TAG . '_' . $categoryId;
            }, $categoryIds);

            $identities = array_merge($identities, $categoryTags);
        }

        return $identities;
    }

    /**
     * Retrieve maximum count cacheable search terms by Store.
     *
     * @param int $storeId Store Id
     *
     * @return int
     */
    private function getMaxCountCacheableSearchTerms(int $storeId)
    {
        return $this->scopeConfig->getValue(
            PopularSearchTerms::XML_PATH_MAX_COUNT_CACHEABLE_SEARCH_TERMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
