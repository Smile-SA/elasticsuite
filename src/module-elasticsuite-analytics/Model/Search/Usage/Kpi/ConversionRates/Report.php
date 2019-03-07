<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
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
            if ($value->getValue() == 'all') {
                $sessions   = $response->count();
                $sales      = (int) $value->getMetrics()['product_sale'];
                if ($sessions > 0) {
                    $data['all'] = (float) $sales / $sessions;
                }
            } elseif ($value->getValue() == 'searches') {
                $sessions   = (int) $value->getMetrics()['count'];
                $sales      = (int) $value->getMetrics()['product_sale'];
                if ($sessions > 0) {
                    $data['searches'] = (float) $sales / $sessions;
                }
            } else {
                $sessions   = (int) $value->getMetrics()['count'];
                $sales      = (int) $value->getMetrics()['product_sale'];
                if ($sessions > 0) {
                    $data['no_searches'] = (float) $sales / $sessions;
                }
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
