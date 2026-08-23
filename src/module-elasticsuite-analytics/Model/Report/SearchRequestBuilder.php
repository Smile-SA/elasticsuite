<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Report;

/**
 * Search request builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
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
     * @var boolean
     */
    private $trackTotalHits = true;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder Search request builder.
     * @param Context                                        $context              Report context.
     * @param AggregationProviderInterface[]                 $aggregationProviders Aggregation providers.
     * @param QueryProviderInterface[]                       $queryProviders       Query providers.
     * @param string                                         $containerName        Search request container name.
     * @param bool                                           $trackTotalHits       Whether to track total hits.
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        Context $context,
        array $aggregationProviders = [],
        array $queryProviders = [],
        $containerName = 'tracking_log_event',
        $trackTotalHits = true
    ) {
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->context              = $context;
        $this->containerName        = $containerName;
        $this->aggregationProviders = $aggregationProviders;
        $this->queryProviders       = $queryProviders;
        $this->trackTotalHits       = $trackTotalHits;
    }

    /**
     * Get request.
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    public function getRequest()
    {
        $storeId      = $this->context->getStoreId();
        $aggregations = $this->getAggregations();
        $searchQuery  = $this->getSearchQuery();

        return $this->searchRequestBuilder->create(
            $storeId,
            $this->containerName,
            0,
            0,
            null,
            [],
            [],
            $searchQuery,
            $aggregations,
            $this->trackTotalHits
        );
    }

    /**
     * Get query filters.
     *
     * @return array
     */
    private function getSearchQuery()
    {
        $queries = [];

        foreach ($this->queryProviders as $queryProvider) {
            $queries[] = $queryProvider->getQuery();
        }

        return array_filter($queries);
    }

    /**
     * Get aggregations.
     *
     * @return array
     */
    private function getAggregations()
    {
        $aggs = [];

        foreach ($this->aggregationProviders as $aggregationProvider) {
            $aggs[] = $aggregationProvider->getAggregation();
        }

        return $aggs;
    }
}
