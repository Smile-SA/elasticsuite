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

namespace Smile\ElasticsuiteCore\Model;

/**
 * SearchInterface implementation using elasticsuite.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Search implements \Magento\Search\Api\SearchInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Model\Search\RequestBuilder
     */
    private $searchRequestBuilder;

    /**
     * @var \Magento\Framework\Search\SearchEngineInterface
     */
    private $searchEngine;

    /**
     * @var \Magento\Framework\Search\SearchResponseBuilder
     */
    private $searchResponseBuilder;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Search\SearchEngineInterface     $searchEngine          Search engine.
     * @param \Smile\ElasticsuiteCore\Model\Search\RequestBuilder $searchRequestBuilder  Search request builder.
     * @param \Magento\Framework\Search\SearchResponseBuilder     $searchResponseBuilder Search response builder.
     */
    public function __construct(
        \Magento\Framework\Search\SearchEngineInterface $searchEngine,
        \Smile\ElasticsuiteCore\Model\Search\RequestBuilder $searchRequestBuilder,
        \Magento\Framework\Search\SearchResponseBuilder $searchResponseBuilder
    ) {
            $this->searchRequestBuilder  = $searchRequestBuilder;
            $this->searchEngine          = $searchEngine;
            $this->searchResponseBuilder = $searchResponseBuilder;
    }

    /**
     * Execute search.
     *
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria Search criteria.
     *
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     */
    public function search(\Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria)
    {
        $searchRequest  = $this->searchRequestBuilder->getRequest($searchCriteria);
        $searchResponse = $this->searchEngine->search($searchRequest);
        $searchResult   = $this->searchResponseBuilder->build($searchResponse);

        $totalCount = $searchResponse->count();
        $searchResult->setTotalCount($totalCount);
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }
}
