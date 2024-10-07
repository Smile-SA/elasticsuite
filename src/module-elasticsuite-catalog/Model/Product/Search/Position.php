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
namespace Smile\ElasticsuiteCatalog\Model\Product\Search;

use Magento\Search\Model\PopularSearchTerms;

/**
 * Model that handle custom product position for a given search term.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Position
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position
     */
    private $resourceModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Cache\Type\FrontendPool
     */
    private $frontendCachePool;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Search\Model\ResourceModel\Query\Collection
     */
    private $queryCollection;

    /**
     * Position constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel         Resource
     * @param \Magento\Store\Model\StoreManagerInterface                             $storeManagerInterface Store Manager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                     $scopeConfig           Scope Config
     * @param \Magento\Search\Model\ResourceModel\Query\Collection                   $queryCollection       Query Collection
     * @param \Magento\Framework\App\Cache\Type\FrontendPool                         $frontendCachePool     Frontend Cache
     */
    public function __construct(
        \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Search\Model\ResourceModel\Query\Collection $queryCollection,
        \Magento\Framework\App\Cache\Type\FrontendPool $frontendCachePool
    ) {
        $this->resourceModel     = $resourceModel;
        $this->storeManager      = $storeManagerInterface;
        $this->frontendCachePool = $frontendCachePool;
        $this->scopeConfig       = $scopeConfig;
        $this->queryCollection   = $queryCollection;
    }

    /**
     * Save the product positions.
     *
     * @param int   $queryId             Query id.
     * @param array $newProductPositions Product positions.
     * @param array $blacklistedProducts Blacklisted product ids.
     *
     * @return void
     */
    public function saveProductPositions($queryId, $newProductPositions, $blacklistedProducts = [])
    {
        $this->resourceModel->saveProductPositions($queryId, $newProductPositions, $blacklistedProducts);

        foreach ($this->storeManager->getStores() as $store) {
            if ($this->isPopularQuery($queryId, $store->getId())) {
                $this->cleanPopularSearchCache();
            }
        }
    }

    /**
     * Check if a query Id is among the popular query list for a given store.
     *
     * @param int $queryId The Query Id
     * @param int $storeId The Store Id
     *
     * @return bool
     */
    private function isPopularQuery($queryId, $storeId)
    {
        $popularQueryIds = $this->queryCollection
            ->setPopularQueryFilter($storeId)
            ->setPageSize($this->getMaxCountCacheableSearchTerms($storeId))
            ->load()
            ->getColumnValues('query_id');

        return count(array_intersect([$queryId], $popularQueryIds)) > 0 ;
    }

    /**
     * Cleanup popular searches cache tag.
     */
    private function cleanPopularSearchCache()
    {
        try {
            $this->frontendCachePool->get(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER)->clean(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                [\Smile\ElasticsuiteCatalog\Block\CatalogSearch\Result\Cache::POPULAR_SEARCH_CACHE_TAG]
            );
        } catch (\InvalidArgumentException $exception) {
            ;
        }
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
