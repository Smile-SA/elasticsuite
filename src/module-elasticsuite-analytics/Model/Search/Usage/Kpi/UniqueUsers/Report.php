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

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Magento\Framework\Api\Search\AggregationValueInterface;

/**
 * Unique sessions and visitors KPI report.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Report extends AbstractReport
{
    /**
     * {@inheritDoc}
     */
    protected function processResponse(QueryResponse $response)
    {
        $data = ['sessions_count' => $response->count(), 'visitors_count' => 0];

        $uniqueVisitors = $this->getBucketValues($response, 'unique_visitors');
        if (!empty($uniqueVisitors)) {
            $uniqueVisitors = current($uniqueVisitors);
            $data['visitors_count'] = (int) $uniqueVisitors->getMetrics()['value'] ?? 0;
        }

        return $data;
    }

    /**
     * Return the bucket values from the specified aggregation.
     *
     * @param QueryResponse $response ES Query response.
     * @param string        $aggName  Aggregation name.
     *
     * @return AggregationValueInterface[]
     */
    private function getBucketValues(QueryResponse $response, string $aggName)
    {
        $bucket = $response->getAggregations()->getBucket($aggName);

        return $bucket !== null ? $bucket->getValues() : [];
    }
}
