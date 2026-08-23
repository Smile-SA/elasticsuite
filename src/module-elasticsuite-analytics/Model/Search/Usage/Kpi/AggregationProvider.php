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
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;

/**
 * Search usage KPI AggregationProvider
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
     * AggregationProvider constructor.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory Aggregation factory.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory
    ) {
        $this->aggregationFactory   = $aggregationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregation()
    {
        $aggParams = [
            'name'       => 'unique_sessions',
            'type'       => BucketInterface::TYPE_METRIC,
            'field'      => 'session.uid',
            'metricType' => MetricInterface::TYPE_CARDINALITY,
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_METRIC, $aggParams);
    }
}
