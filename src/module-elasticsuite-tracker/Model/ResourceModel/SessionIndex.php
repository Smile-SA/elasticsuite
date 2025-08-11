<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\ResourceModel;

use Magento\Framework\Api\Search\AggregationValueInterface;
use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Session index resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SessionIndex
{
    /**
     * @var string
     */
    private const SEARCH_REQUEST_CONTAINER = 'session_aggregator';

    /**
     * @var Builder
     */
    private $searchRequestBuilder;

    /**
     * @var SearchEngineInterface
     */
    private $searchEngine;

    /**
     * Constructor.
     *
     * @param Builder               $searchRequestBuilder Search request builder.
     * @param SearchEngineInterface $searchEngine         Search engine.
     */
    public function __construct(
        Builder $searchRequestBuilder,
        \Magento\Framework\Search\SearchEngineInterface $searchEngine
    ) {
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->searchEngine         = $searchEngine;
    }

    /**
     * Retrieve session data to be indexed.
     *
     * @param int      $storeId    Store id.
     * @param string[] $sessionIds Session ids.
     *
     * @return array
     */
    public function getSessionData($storeId, $sessionIds): array
    {
        $data = [];
        $searchRequest  = $this->getSearchRequest($storeId, $sessionIds);
        $searchResponse = $this->searchEngine->search($searchRequest);
        if ($searchResponse->getAggregations()->getBucket('session_id') !== null) {
            foreach ($searchResponse->getAggregations()->getBucket('session_id')->getValues() as $sessionValue) {
                $sessionData = $this->processSessionData($sessionValue, 'session_id');
                $sessionData['store_id'] = $storeId;
                unset($sessionData['count']);

                $data[] = array_filter($sessionData);
            }
        }

        return $data;
    }

    /**
     * Build search request used to collect aggregated session data.
     *
     * @param int      $storeId    Current store Id.
     * @param string[] $sessionIds Session ids.
     *
     * @return RequestInterface
     */
    private function getSearchRequest($storeId, $sessionIds): RequestInterface
    {
        $queryFilters = ['session.uid' => array_values(array_unique($sessionIds))];

        return $this->searchRequestBuilder->create($storeId, self::SEARCH_REQUEST_CONTAINER, 0, 0, null, [], [], $queryFilters);
    }

    /**
     * Prepare session data from search aggregation response.
     *
     * @param AggregationValueInterface $value Aggregation value.
     *
     * @return array
     */
    private function processSessionData(AggregationValueInterface $value, string $aggregationName): array
    {
        $data = [$aggregationName => $value->getValue()];

        /** @var Value $value */
        foreach ($value->getAggregations()->getBuckets() as $bucket) {
            $bucketName   = $bucket->getName();
            $bucketValues = [];

            foreach ($bucket->getValues() as $bucketValue) {
                if ($bucketValue->getAggregations()->getBuckets()) {
                    // Handle nested aggregations here.
                    foreach ($bucketValue->getAggregations()->getBuckets() as $subBucket) {
                        $subBucketValues = [];
                        foreach ($subBucket->getValues() as $subBucketValue) {
                            $subBucketValues[] = $subBucketValue->getValue();
                        }
                        $bucketValues[] = [
                            'name' => $bucketValue->getValue(),
                            $subBucket->getName() => $subBucketValues,
                        ];
                    }
                } else {
                    $bucketValues[] = $bucketValue->getValue();
                }
            }

            $data[$bucketName] = $bucketValues;
        }

        foreach ($value->getMetrics() as $metricName => $metricValue) {
            $data[$metricName] = $metricValue;
        }

        return $data;
    }
}
