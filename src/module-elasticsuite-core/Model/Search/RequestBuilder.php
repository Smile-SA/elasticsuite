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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search;

use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterfaceFactory;
use Smile\ElasticsuiteCore\Search\Request\Builder;

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
     * @var Builder
     */
    private $searchRequestBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ContainerConfigurationInterfaceFactory
     */
    private $containerConfigFactory;

    /**
     * @var RequestMapper
     */
    private $requestMapper;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param Builder                                $searchRequestBuilder   Search request builder.
     * @param StoreManagerInterface                  $storeManager           Store resolver.
     * @param ContainerConfigurationInterfaceFactory $containerConfigFactory Container config factory.
     * @param ContextInterface                       $searchContext          Search context.
     * @param QueryFactory                           $queryFactory           Search query factory
     * @param RequestMapper                          $requestMapper          Request mapper.
     */
    public function __construct(
        Builder $searchRequestBuilder,
        StoreManagerInterface $storeManager,
        ContainerConfigurationInterfaceFactory $containerConfigFactory,
        ContextInterface $searchContext,
        QueryFactory $queryFactory,
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

        $this->updateSearchContext($storeId, $queryText);

        $sortOrders = $this->requestMapper->getSortOrders($containerConfiguration, $searchCriteria);
        $filters    = $this->requestMapper->getFilters($containerConfiguration, $searchCriteria);

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
