<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;

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
     *
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory $metricFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory
    ) {
        $this->aggregationFactory   = $aggregationFactory;
        $this->metricFactory        = $metricFactory;
        $this->queryFactory         = $queryFactory;
    }

    public function getAggregation()
    {
        $aggParams = [
            'name'    => 'data',
            'queries' => $this->getQueries(),
            'metrics' => $this->getMetrics(),
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_QUERY_GROUP, $aggParams);
    }

    private function getMetrics()
    {
        $metrics = [
            $this->metricFactory->create(['name' => 'unique_sessions', 'field' => 'session.uid', 'type' => MetricInterface::TYPE_CARDINALITY]),
            $this->metricFactory->create(['name' => 'unique_visitors', 'field' => 'session.vid', 'type' => MetricInterface::TYPE_CARDINALITY]),
        ];

        return $metrics;
    }

    private function getQueries()
    {
        $queries = [
            'all'      => $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                []
            ),
            'searches' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalogsearch_result_index'
                ]
            ),
        ];

        return $queries;
    }
}
