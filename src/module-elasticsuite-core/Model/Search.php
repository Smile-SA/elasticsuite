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

namespace Smile\ElasticsuiteCore\Model;

use Magento\Framework\Search\SearchEngineInterface;
use Magento\Framework\Search\SearchResponseBuilder;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Model\Search\RequestBuilder;

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
     * @var RequestBuilder
     */
    private $searchRequestBuilder;

    /**
     * @var SearchEngineInterface
     */
    private $searchEngine;

    /**
     * @var SearchResponseBuilder
     */
    private $searchResponseBuilder;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param SearchEngineInterface $searchEngine          Search engine.
     * @param RequestBuilder        $searchRequestBuilder  Search request builder.
     * @param SearchResponseBuilder $searchResponseBuilder Search response builder.
     * @param ContextInterface      $searchContext         Search context.
     */
    public function __construct(
        SearchEngineInterface $searchEngine,
        RequestBuilder $searchRequestBuilder,
        SearchResponseBuilder $searchResponseBuilder,
        ContextInterface $searchContext
    ) {
        $this->searchRequestBuilder  = $searchRequestBuilder;
        $this->searchEngine          = $searchEngine;
        $this->searchResponseBuilder = $searchResponseBuilder;
        $this->searchContext         = $searchContext;
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

        $query = $this->searchContext->getCurrentSearchQuery();

        $totalCount = $searchResponse->count();
        $searchResult->setTotalCount($totalCount);
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setData('is_spellchecked', (bool) $searchRequest->isSpellchecked());
        $searchResult->setData('query_id', ($query && $query->getId()) ? (int) $query->getId() : null);

        return $searchResult;
    }
}
