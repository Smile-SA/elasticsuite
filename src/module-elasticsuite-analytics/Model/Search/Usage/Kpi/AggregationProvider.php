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
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
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
            'childBuckets' => $this->getChildBuckets(),
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
                ['name' => 'unique_sessions', 'field' => 'session.uid', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
            $this->metricFactory->create(
                ['name' => 'unique_visitors', 'field' => 'session.vid', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
            $this->metricFactory->create(
                ['name' => 'spellcheck_usage', 'field' => 'page.search.is_spellchecked', 'type' => MetricInterface::TYPE_STATS]
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
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalogsearch_result_index',
                ]
            ),
            'product_views' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalog_product_view',
                ]
            ),
            'category_views' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalog_category_view',
                ]
            ),
            'add_to_cart' => $this->queryFactory->create(
                QueryInterface::TYPE_EXISTS,
                [
                    'field' => 'page.cart.product_id',
                ]
            ),
            'sales' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'checkout_onepage_success',
                ]
            ),
        ];

        return $queries;
    }

    /**
     * Return child bucket for query group aggregation.
     *
     * @return array
     */
    private function getChildBuckets(): array
    {
        return [
            'origin' => $this->aggregationFactory->create(
                BucketInterface::TYPE_TERM,
                ['name' => 'origin', 'field' => 'previous_page.type.identifier.keyword']
            ),
        ];
    }
}
