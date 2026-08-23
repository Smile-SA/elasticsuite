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

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\Spellcheck;

use Magento\Framework\Api\Search\AggregationValueInterface;
use Smile\ElasticsuiteAnalytics\Model\AbstractReport;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;

/**
 * Spellcheck usage KPI report.
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
        $data = ['spellcheck_usage_count' => 0, 'spellcheck_usage_rate' => 0];

        $spellcheckUsage = $this->getBucketValues($response, 'spellcheck_usage');
        if (!empty($spellcheckUsage)) {
            $spellcheckUsage = current($spellcheckUsage);
            $data['spellcheck_usage_count']  = (int) $spellcheckUsage->getMetrics()['sum'];
            $data['spellcheck_usage_rate']   = $spellcheckUsage->getMetrics()['avg'];
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
