<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Response;

use Magento\Framework\Api\Search\AggregationInterfaceFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Search\Document;
use Magento\Framework\Search\ResponseInterface;
use Magento\Framework\Search\Adapter\Mysql\AggregationFactory;

/**
 * ElasticSuite search adapter response.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
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

    /**
     * Constructor
     *
     * @param DocumentFactory    $documentFactory    Document factory
     * @param AggregationFactory $aggregationFactory Aggregation factory (@todo replace with non MySQL implemenation)..
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
    public function count()
    {
        return $this->count;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
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
     * Build buckets from raw search response.
     *
     * @param array              $searchResponse     Engine raw search response.
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     *
     * @return void
     */
    private function prepareAggregations(array $searchResponse, AggregationFactory $aggregationFactory)
    {
        $buckets = [];

        if (isset($searchResponse['aggregations'])) {
            foreach ($searchResponse['aggregations'] as $bucketName => $aggregation) {
                while (isset($aggregation[$bucketName])) {
                    $aggregation = $aggregation[$bucketName];
                }

                if (isset($aggregation['buckets'])) {
                    foreach ($aggregation['buckets'] as $currentBuket) {
                        $buckets[$bucketName][$currentBuket['key']] = [
                            'value' => $currentBuket['key'],
                            'count' => $currentBuket['doc_count'],
                        ];
                    }
                }
            }
        }

        $this->aggregations = $aggregationFactory->create($buckets);
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

            $this->count = $searchResponse['hits']['total'];
        }
    }
}
