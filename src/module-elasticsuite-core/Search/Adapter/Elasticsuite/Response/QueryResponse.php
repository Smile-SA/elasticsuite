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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Search\ResponseInterface;

/**
 * ElasticSuite search adapter response.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryResponse implements ResponseInterface
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

    /**
     * @var array
     */
    protected $totalHits = [];

    /**
     * Constructor
     *
     * @param DocumentFactory    $documentFactory    Document factory.
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     * @param array              $searchResponse     Engine raw response.
     */
    public function __construct(
        DocumentFactory $documentFactory,
        AggregationFactory $aggregationFactory,
        array $searchResponse
    ) {
        $this->prepareDocuments($searchResponse, $documentFactory);
        $this->prepareAggregations($searchResponse, $aggregationFactory);
    }

    /**
     * {@inheritDoc}
     */
    public function count() : int
    {
        return $this->count;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->documents);
    }

    /**
     * {@inheritDoc}
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Get total hits as given by the Elasticsearch server.
     * Can be useful to check if there's been real hits, eg when querying with size:0 and track_total_hits:true
     * we can receive response like :
     *
     * "total": {
     *     "value": 0,
     *     "relation": "gte"
     * }
     *
     * @return array
     */
    public function getTotalHits()
    {
        return $this->totalHits;
    }

    /**
     * Return true if the responses has results.
     * We cannot trust only count() for this.
     *
     * @return bool
     */
    public function hasResults()
    {
        if (isset($this->totalHits['relation']) && (isset($this->totalHits['value']))) {
            return ($this->totalHits['relation'] === 'gte' && $this->totalHits['value'] >= 0)
                || ($this->totalHits['relation'] === 'eq' && $this->totalHits['value'] > 0);
        }

        return $this->count > 0;
    }

    /**
     * Build buckets from raw search response.
     *
     * @param array              $searchResponse     Engine raw search response.
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     *
     * @return void
     */
    private function prepareAggregations(array $searchResponse, AggregationFactory $aggregationFactory)
    {
        $aggregations = [];

        if (isset($searchResponse['aggregations'])) {
            $aggregations = $searchResponse['aggregations'];
        }

        $this->aggregations = $aggregationFactory->create($aggregations);
    }

    /**
     * Build document list from the engine raw search response.
     *
     * @param array           $searchResponse  Engine raw search response.
     * @param DocumentFactory $documentFactory Document factory
     *
     * @return void
     */
    private function prepareDocuments(array $searchResponse, DocumentFactory $documentFactory)
    {
        $this->documents = [];

        if (isset($searchResponse['hits'])) {
            $hits = $searchResponse['hits']['hits'];

            foreach ($hits as $hit) {
                $this->documents[] = $documentFactory->create($hit);
            }

            // @codingStandardsIgnoreStart
            $this->count = is_array($searchResponse['hits']['total'])
                ? $searchResponse['hits']['total']['value']
                : $searchResponse['hits']['total'];
            // @codingStandardsIgnoreEnd

            $this->totalHits = $searchResponse['hits']['total'];
        }
    }
}
