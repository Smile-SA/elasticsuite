<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\ResourceModel;

use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

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
     * @var array
     */
    private $buckets = [
      [
        'name'         => 'session_id',
        'field'        => 'session.uid',
        'type'         => BucketInterface::TYPE_TERM,
        'childBuckets' => [
            ['name' => 'visitor_id', 'type' => BucketInterface::TYPE_TERM, 'field' => 'session.vid'],
            ['name' => 'product_view', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.product.id'],
            ['name' => 'category_view', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.category.id'],
            ['name' => 'category_view', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.category.id'],
            ['name' => 'search_query', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.search.query.sortable'],
            ['name' => 'product_cart', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.cart.product_id'],
            ['name' => 'product_sale', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.order.items.product_id'],
            ['name' => 'category_sale', 'type' => BucketInterface::TYPE_TERM, 'field' => 'page.order.items.category_ids'],
        ],
        'metrics'      => [
          ['name' => 'start_date', 'type' => MetricInterface::TYPE_MIN, 'field' => 'date'],
          ['name' => 'end_date', 'type' => MetricInterface::TYPE_MAX, 'field' => 'date'],
        ],
      ],
    ];

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $searchRequestBuilder;

    /**
     * @var \Magento\Framework\Search\SearchEngineInterface
     */
    private $searchEngine;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder  $searchRequestBuilder Search request builder.
     * @param \Magento\Framework\Search\SearchEngineInterface $searchEngine         Search engine.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
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
    public function getSessionData($storeId, $sessionIds)
    {
        $data = [];
        $searchRequest  = $this->getSearchRequest($storeId, $sessionIds);
        $searchResponse = $this->searchEngine->search($searchRequest);

        foreach ($searchResponse->getAggregations()->getBucket('session_id')->getValues() as $sessionValue) {
            $sessionData = $this->processSessionData($sessionValue);
            $sessionData['store_id'] = $storeId;
            unset($sessionData['count']);

            $data[] = array_filter($sessionData);
        }

        return $data;
    }

    /**
     * Build search request used to collect aggregated session data.
     *
     * @param int      $storeId    Current store Id.
     * @param string[] $sessionIds Session ids.
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    private function getSearchRequest($storeId, $sessionIds)
    {
        $eventIndexIdentifier = EventIndexInterface::INDEX_IDENTIFIER;

        $queryFilters = ['session.uid' => $sessionIds];

        return $this->searchRequestBuilder->create($storeId, $eventIndexIdentifier, 0, 0, null, [], [], $queryFilters, $this->buckets);
    }

    /**
     * Prepare session data from search aggregation response.
     *
     * @param \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value $value Aggregation value.
     *
     * @return array
     */
    private function processSessionData(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Aggregation\Value $value)
    {
        $data = ['session_id' => $value->getValue()];

        foreach ($value->getAggregations()->getBuckets() as $bucket) {
            $bucketName   = $bucket->getName();
            $bucketValues = [];

            foreach ($bucket->getValues() as $bucketValue) {
                $bucketValues[] = $bucketValue->getValue();
            }

            $data[$bucketName] = $bucketValues;
        }

        foreach ($value->getMetrics() as $metricName => $metricValue) {
            $data[$metricName] = $metricValue;
        }

        return $data;
    }
}
