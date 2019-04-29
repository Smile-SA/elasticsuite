<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search;

/**
 * ElasticSuite search API implementation : convert search criteria to search request.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class RequestBuilder
{
    /**
     * @var integer
     */
    const DEFAULT_PAGE_SIZE = 20;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $searchRequestBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterfaceFactory
     */
    private $containerConfigFactory;

    /**
     * @var RequestMapper
     */
    private $requestMapper;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder                                    $searchRequestBuilder   Search request
     *                                                                                                                  builder.
     * @param \Magento\Store\Model\StoreManagerInterface                                        $storeManager           Store resolver.
     * @param \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterfaceFactory $containerConfigFactory Container config
     *                                                                                                                  factory.
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface                               $searchContext          Search context.
     * @param \Magento\Search\Model\QueryFactory                                                $queryFactory           Search query factory
     * @param RequestMapper                                                                     $requestMapper          Request mapper.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterfaceFactory $containerConfigFactory,
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
        \Magento\Search\Model\QueryFactory $queryFactory,
        RequestMapper $requestMapper
    ) {
        $this->searchRequestBuilder   = $searchRequestBuilder;
        $this->storeManager           = $storeManager;
        $this->requestMapper          = $requestMapper;
        $this->containerConfigFactory = $containerConfigFactory;
        $this->searchContext          = $searchContext;
        $this->queryFactory           = $queryFactory;
    }

    /**
     * Build a search request from a search criteria.
     *
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria Search criteria.
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    public function getRequest(\Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria)
    {
        $storeId = $this->getCurrentStoreId();

        $containerName = $searchCriteria->getRequestName();

        $containerConfiguration = $this->getSearchContainerConfiguration($storeId, $containerName);

        $size = $searchCriteria->getPageSize() ?? self::DEFAULT_PAGE_SIZE;
        $from = max(0, (int) $searchCriteria->getCurrentPage() - 1) * $size;

        $queryText  = $this->getFulltextFilter($searchCriteria);

        $sortOrders = $this->requestMapper->getSortOrders($containerConfiguration, $searchCriteria);
        $filters    = $this->requestMapper->getFilters($containerConfiguration, $searchCriteria);

        $this->updateSearchContext($storeId, $queryText);

        return $this->searchRequestBuilder->create($storeId, $containerName, $from, $size, $queryText, $sortOrders, $filters, []);
    }

    /**
     * Update the search context using current store id and query text.
     *
     * @param integer $storeId   Store id.
     * @param string  $queryText Fulltext query text.
     *
     * @return void
     */
    private function updateSearchContext($storeId, $queryText)
    {
        $this->searchContext->setStoreId($storeId);
        $query = $this->queryFactory->create();
        $query->setStoreId($storeId);
        $query->loadByQueryText($queryText);

        if ($query->getId()) {
            $this->searchContext->setCurrentSearchQuery($query);
        }
    }

    /**
     * Return current store id.
     *
     * @return integer
     */
    private function getCurrentStoreId()
    {
        $storeId = $this->storeManager->getStore()->getId();

        if ($storeId == 0) {
            $storeId = $this->storeManager->getDefaultStoreView()->getId();
        }

        return $storeId;
    }

    /**
     * Extract fulltext search query from search criteria.
     *
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria Search criteria.
     *
     * @return NULL|string
     */
    private function getFulltextFilter(\Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria)
    {
        $queryText = null;

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == "search_term") {
                    $queryText = $filter->getValue();
                }
            }
        }

        return $queryText;
    }

    /**
     * Get current search container.
     *
     * @param int    $storeId       Store id.
     * @param string $containerName Container name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface
     */
    private function getSearchContainerConfiguration($storeId, $containerName)
    {
        return $this->containerConfigFactory->create(['storeId' => $storeId, 'containerName' => $containerName]);
    }
}
