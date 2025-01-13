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
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Default AggregationProvider
 *
 * @catgory  Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class AggregationProvider implements AggregationProviderInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    protected $aggregationFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory
     */
    protected $metricFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory
     */
    protected $pipelineFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    protected $queryFactory;

    /**
     * @var \Smile\ElasticsuiteAnalytics\Helper\Data
     */
    protected $helper;

    /**
     * AggregationProvider constructor.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory Bucket aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory      $metricFactory      Metrics aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory    $pipelineFactory    Pipeline aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory             $queryFactory       Query Factory.
     * @param \Smile\ElasticsuiteAnalytics\Helper\Data                              $helper             Data helper.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory $metricFactory,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\PipelineFactory $pipelineFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteAnalytics\Helper\Data $helper
    ) {
        $this->aggregationFactory   = $aggregationFactory;
        $this->metricFactory        = $metricFactory;
        $this->pipelineFactory      = $pipelineFactory;
        $this->queryFactory         = $queryFactory;
        $this->helper               = $helper;
    }

    /**
     * Return the main bucket aggregation
     *
     * @return BucketInterface
     */
    public function getAggregation()
    {
        $aggParams = [
            'field'     => 'page.search.query.sortable',
            'name'      => 'search_terms',
            'metrics'   => $this->getMetrics(),
            'pipelines' => $this->getPipelines(),
            'childBuckets'  => [$this->getFilteredResultCountMetric()],
            'sortOrder' => ['unique_sessions' => 'desc'],
            'size'      => $this->helper->getMaxSearchTerms(),
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_TERM, $aggParams);
    }

    /**
     * Return metrics aggregations to apply to the main bucket aggregation
     *
     * @return array
     */
    protected function getMetrics()
    {
        $metrics = [
            $this->metricFactory->create(
                ['name' => 'unique_sessions', 'field' => 'session.uid', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
            $this->metricFactory->create(
                ['name' => 'unique_visitors', 'field' => 'session.vid', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
            // Metrics result_count moved to a sub-aggregation to ignore filtered search pages.
        ];

        return $metrics;
    }

    /**
     * Return pipeline aggregations to apply to the main bucket aggregation
     *
     * @return array
     */
    protected function getPipelines()
    {
        $pipelines = [];

        return $pipelines;
    }

    /**
     * Return aggregation providing the filtered (no filtered search pages) result/product count metrics.
     *
     * @return BucketInterface
     */
    protected function getFilteredResultCountMetric()
    {
        return $this->aggregationFactory->create(
            BucketInterface::TYPE_METRIC,
            [
                'name'          => 'result_count',
                'metricType'    => MetricInterface::TYPE_AVG,
                'field'         => 'page.product_list.product_count',
                'filter'        => $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->getIsFirstPageQuery(),
                        ],
                        'mustNot' => [
                            $this->hasNavigationFiltersQuery(),
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * Return "only first page" query.
     *
     * @return QueryInterface
     */
    protected function getIsFirstPageQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            [
                'field' => 'page.product_list.current_page',
                'value' => 1,
            ]
        );
    }

    /**
     * Return query indicating there are active product list filters.
     *
     * @return QueryInterface
     */
    protected function hasNavigationFiltersQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path'  => 'page.product_list.filters',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_EXISTS,
                    ['field' => 'page.product_list.filters']
                ),
            ]
        );
    }
}
