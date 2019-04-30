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

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;

/**
 * Class Report
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Report extends AbstractReport
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = [];

        foreach ($this->getBucketValues($response) as $value) {
            $sessionType = $value->getValue();
            if ($sessionType == 'all') {
                $sessions = $response->count();
            } else {
                $sessions = (int) $value->getMetrics()['count'];
            }
            $sales = 0;
            if ($conversionBucket = $value->getAggregations()->getBucket('conversion')) {
                $sales = (int) current($conversionBucket->getValues())->getMetrics()['count'];
            }
            if ($sessions > 0) {
                $data[$sessionType] = (float) $sales / $sessions;
            }
        }

        return $data;
    }

    /**
     * Return the bucket values from the main aggregation
     *
     * @param \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response ES Query response.
     *
     * @return \Magento\Framework\Api\Search\AggregationValueInterface[]
     */
    private function getBucketValues(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $bucket = $response->getAggregations()->getBucket('data');

        return $bucket !== null ? $bucket->getValues() : [];
    }
}
