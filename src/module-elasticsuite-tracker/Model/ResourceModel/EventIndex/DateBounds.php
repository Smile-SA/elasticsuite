<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\ResourceModel\EventIndex;

use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\Context;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\Metric;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Session index resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Botis <botis@smile.fr>
 */
class DateBounds
{
    /**
     * @var Builder
     */
    protected $searchRequestBuilder;

    /**
     * @var SearchEngineInterface
     */
    protected $searchEngine;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var AggregationFactory
     */
    protected $aggregationFactory;

    /**
     * @var MetricFactory
     */
    protected $metricFactory;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * Constructor.
     *
     * @param Builder               $searchRequestBuilder Search request builder
     * @param SearchEngineInterface $searchEngine         Search engine
     * @param Context               $context              Context
     * @param AggregationFactory    $aggregationFactory   Aggregation factory
     * @param MetricFactory         $metricFactory        Metric factory
     * @param QueryFactory          $queryFactory         Query factory
     */
    public function __construct(
        Builder $searchRequestBuilder,
        SearchEngineInterface $searchEngine,
        Context $context,
        AggregationFactory $aggregationFactory,
        MetricFactory $metricFactory,
        QueryFactory $queryFactory
    ) {
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->searchEngine         = $searchEngine;
        $this->context              = $context;
        $this->aggregationFactory   = $aggregationFactory;
        $this->metricFactory        = $metricFactory;
        $this->queryFactory         = $queryFactory;
    }

    /**
     * @return array
     */
    public function getIndicesDateBounds(): array
    {
        $boundsDate = ['minDate' => null, 'maxDate' => null];
        $searchRequest  = $this->getSearchRequest();
        $searchResponse = $this->searchEngine->search($searchRequest);
        if ($searchResponse->getAggregations()->getBucket('bounds') !== null) {
            foreach ($searchResponse->getAggregations()->getBucket('bounds')->getValues() as $aggValue) {
                $metrics = $aggValue->getMetrics();
                if (($metrics['count'] ?? 0) > 0) {
                    $boundsDate['minDate'] = $metrics['minDate'] ?? null;
                    $boundsDate['maxDate'] = $metrics['maxDate'] ?? null;
                }
            }
        }

        return $boundsDate;
    }

    /**
     * Build search request used to collect aggregated session data.
     *
     * @return RequestInterface
     */
    protected function getSearchRequest(): RequestInterface
    {
        $storeId      = $this->context->getStoreId();
        $aggregations = $this->getAggregations();

        return $this->searchRequestBuilder->create(
            $storeId,
            'tracking_log_event',
            0,
            0,
            null,
            [],
            [],
            [],
            $aggregations,
            false
        );
    }

    /**
     * Get aggregations.
     *
     * @return BucketInterface[]
     */
    protected function getAggregations(): array
    {
        return [
            $this->aggregationFactory->create(
                BucketInterface::TYPE_QUERY_GROUP,
                [
                    'queries' => [
                        'all' => $this->queryFactory->create(
                            QueryInterface::TYPE_BOOL,
                            []
                        ),
                    ],
                    'name'    => 'bounds',
                    'size'    => 0,
                    'metrics' => $this->getMetrics(),
                ]
            ),
        ];
    }

    /**
     * Get metrics.
     *
     * @return Metric[]
     */
    protected function getMetrics(): array
    {
        return [
            $this->metricFactory->create(
                ['name' => 'minDate', 'field' => 'date', 'type' => MetricInterface::TYPE_MIN]
            ),
            $this->metricFactory->create(
                ['name' => 'maxDate', 'field' => 'date', 'type' => MetricInterface::TYPE_MAX]
            ),
        ];
    }
}
