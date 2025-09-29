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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms\NoResultTerms;

use Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms\AggregationProvider as TermsAggregationProvider;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Aggregation provider for terms that always return 0 results
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class AggregationProvider extends TermsAggregationProvider
{
    /**
     * {@inheritdoc}
     */
    public function getAggregation()
    {
        $aggParams = [
            'field'     => 'search_query_void.sortable',
            'name'      => 'search_terms',
            'metrics'   => $this->getMetrics(),
            'pipelines' => $this->getPipelines(),
            'sortOrder' => ['unique_sessions' => 'desc'],
            'size'      => $this->helper->getMaxSearchTerms(),
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_TERM, $aggParams);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMetrics()
    {
        $metrics = [
            $this->metricFactory->create(
                ['name' => 'unique_sessions', 'field' => 'session_id', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
            $this->metricFactory->create(
                ['name' => 'unique_visitors', 'field' => 'visitor_id', 'type' => MetricInterface::TYPE_CARDINALITY]
            ),
        ];

        return $metrics;
    }
}
