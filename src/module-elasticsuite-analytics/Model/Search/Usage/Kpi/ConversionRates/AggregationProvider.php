<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
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
            'childBuckets' => [ $this->getConversionRateAggregation() ],
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_QUERY_GROUP, $aggParams);
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

    /**
     * Return the child aggregation used to compute the conversion rate of sessions.
     *
     * @return BucketInterface
     */
    private function getConversionRateAggregation()
    {
        return $this->aggregationFactory->create(
            BucketInterface::TYPE_QUERY_GROUP,
            [
                'queries' => [
                    'sales' => $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'product_sale']),
                ],
                'name' => 'conversion',
            ]
        );
    }
}
