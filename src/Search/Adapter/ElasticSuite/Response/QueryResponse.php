<?php

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response;

use Magento\Framework\Api\Search\AggregationInterfaceFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Search\Document;
use Magento\Framework\Search\ResponseInterface;
use Magento\Framework\Search\Adapter\Mysql\DocumentFactory;
use Magento\Framework\Search\Adapter\Mysql\AggregationFactory;

class QueryResponse implements ResponseInterface, \IteratorAggregate, \Countable
{
    /**
     * Document Collection
     *
     * @var Document[]
     */
    protected $documents = [];

    /**
     * @var integer
     */
    protected $count = 0;

    /**
     * Aggregation Collection
     *
     * @var AggregationInterface
     */
    protected $aggregations;

    public function __construct(
        DocumentFactory $documentFactory,
        AggregationFactory $aggregationFactory,
        array $searchResponse
    ) {
        $this->prepareDocuments($searchResponse, $documentFactory);
        $this->prepareAggregations($searchResponse, $aggregationFactory);
    }

    public function count()
    {
        return $this->count;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->documents);
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    private function prepareAggregations($searchResponse, $aggregationFactory)
    {
        $buckets = [];

        if (isset($searchResponse['aggregations'])) {
            foreach ($searchResponse['aggregations'] as $bucketName => $aggregation) {
                if (isset($aggregation['buckets'])) {
                    foreach ($aggregation['buckets'] as $currentBuket) {
                        $buckets[$bucketName][$currentBuket['key']] = [
                            'value' => $currentBuket['key'],
                            'count' => $currentBuket['doc_count']
                        ];
                    }
                }
            }
        }

        $this->aggregations = $aggregationFactory->create($buckets);
    }


    private function prepareDocuments($searchResponse, $documentFactory)
    {
        $this->documents = [];

        if (isset($searchResponse['hits'])) {
            $hits = $searchResponse['hits']['hits'];

            foreach ($hits as $hit) {
                $documentIdField = ['name' => 'entity_id', 'value' => $hit['_id']];
                $scoreField      = ['name' => 'score'    , 'value' => $hit['_score']];
                $this->documents[] =  $documentFactory->create([$documentIdField, $scoreField]);
            }

            $this->count = $searchResponse['hits']['total'];
        }
    }
}
