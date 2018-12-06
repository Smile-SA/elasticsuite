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
    private $searchIndexName;

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
     * @param string                                         $searchIndexName
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        Context $context,
        array $aggregationProviders = [],
        array $queryProviders = [],
        $searchIndexName = \Smile\ElasticsuiteTracker\Api\EventIndexInterface::INDEX_IDENTIFIER
    ) {
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->context              = $context;
        $this->searchIndexName      = $searchIndexName;
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

        return $this->searchRequestBuilder->create($storeId, $this->searchIndexName, 0, 0, null, [], [], $searchQuery, $aggregations);
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