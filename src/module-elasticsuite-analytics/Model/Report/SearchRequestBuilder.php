<?php

namespace Smile\ElasticsuiteAnalytics\Model\Report;

class SearchRequestBuilder
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $searchRequestBuilder;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var string
     */
    private $containerName;

    /**
     * @var QueryProviderInterface[]
     */
    private $queryProviders;

    /**
     * @var AggregationProviderInterface[]
     */
    private $aggregationProviders;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder
     * @param Context                                        $context
     * @param AggregationProviderInterface[]                 $aggregationProviders
     * @param QueryProviderInterface[]                       $queryProviders
     * @param string                                         $containerName
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        Context $context,
        array $aggregationProviders = [],
        array $queryProviders = [],
        $containerName = \Smile\ElasticsuiteTracker\Model\ResourceModel\SessionIndex::SEARCH_REQUEST_CONTAINER
    ) {
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->context              = $context;
        $this->containerName        = $containerName;
        $this->aggregationProviders = $aggregationProviders;
        $this->queryProviders       = $queryProviders;
    }

    /**
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    public function getRequest()
    {
        $storeId      = $this->context->getStoreId();
        $aggregations = $this->getAggregations();
        $searchQuery  = $this->getSearchQuery();

        return $this->searchRequestBuilder->create($storeId, $this->containerName, 0, 0, null, [], [], $searchQuery, $aggregations);
    }

    private function getSearchQuery()
    {
        $queries = [];

        foreach ($this->queryProviders as $queryProvider) {
            $queries[] = $queryProvider->getQuery();
        }

        return array_filter($queries);
    }

    private function getAggregations()
    {
        $aggs = [];

        foreach ($this->aggregationProviders as $aggregationProvider) {
            $aggs[] = $aggregationProvider->getAggregation();
        }

        return $aggs;
    }
}