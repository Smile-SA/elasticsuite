<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\UniqueUsers;

use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Unique sessions and visitors KPI report aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytic
 */
class AggregationProvider implements AggregationProviderInterface
{
    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var MetricFactory
     */
    private $metricFactory;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * AggregationProvider constructor.
     *
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     * @param MetricFactory      $metricFactory      Metric factory.
     * @param QueryFactory       $queryFactory       Query factory.
     */
    public function __construct(
        AggregationFactory $aggregationFactory,
        MetricFactory $metricFactory,
        QueryFactory $queryFactory
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
            'name'       => 'unique_visitors',
            'type'       => BucketInterface::TYPE_METRIC,
            'field'      => 'visitor_id',
            'metricType' => MetricInterface::TYPE_CARDINALITY,
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_METRIC, $aggParams);
    }
}
