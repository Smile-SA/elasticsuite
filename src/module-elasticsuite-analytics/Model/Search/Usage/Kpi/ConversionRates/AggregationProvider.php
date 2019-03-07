<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\ConversionRates;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;

/**
 * Class AggregationProvider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class AggregationProvider implements AggregationProviderInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory
     */
    private $metricFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * AggregationProvider constructor.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory Aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory      $metricFactory      Metric factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory             $queryFactory       Query factory.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory $metricFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory
    ) {
        $this->aggregationFactory   = $aggregationFactory;
        $this->metricFactory        = $metricFactory;
        $this->queryFactory         = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregation()
    {
        $aggParams = [
            'name'    => 'data',
            'queries' => $this->getQueries(),
            'metrics' => $this->getMetrics(),
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_QUERY_GROUP, $aggParams);
    }

    /**
     * Return metrics aggregations to apply to the main bucket aggregation
     *
     * @return array
     */
    private function getMetrics()
    {
        $metrics = [
            $this->metricFactory->create(
                ['name' => 'product_sale', 'field' => 'product_sale', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
        ];

        return $metrics;
    }

    /**
     * Return the queries of the query group aggregation.
     *
     * @return array
     */
    private function getQueries()
    {
        $queries = [
            'all'      => $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                []
            ),
            'searches' => $this->queryFactory->create(
                QueryInterface::TYPE_EXISTS,
                [
                    'field' => 'search_query',
                ]
            ),
            'no_searches' => $this->queryFactory->create(
                QueryInterface::TYPE_MISSING,
                [
                    'field' => 'search_query',
                ]
            ),
        ];

        return $queries;
    }
}
