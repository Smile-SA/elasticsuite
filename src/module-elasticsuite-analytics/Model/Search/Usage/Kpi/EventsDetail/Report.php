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

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\EventsDetail;

use Magento\Framework\Api\Search\AggregationValueInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Smile\ElasticsuiteAnalytics\Model\AbstractReport;

/**
 * Events/page view types detail KPI report.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Report extends AbstractReport
{
    /**
     * @var array
     */
    private $defaultKeys = [
        'page_views_count',
        'product_views_count',
        'category_views_count',
        'add_to_cart_count',
        'sales_count',
    ];

    /**
     * {@inheritdoc}
     */
    protected function processResponse(QueryResponse $response)
    {
        $data = array_merge(array_fill_keys($this->defaultKeys, 0), ['page_views_count' => $response->count()]);

        foreach ($this->getBucketValues($response) as $value) {
            if (in_array($value->getValue(), ['product_views', 'category_views', 'add_to_cart', 'sales'])) {
                $key = sprintf("%s_count", $value->getValue());
                $data[$key] = (int) $value->getMetrics()['count'];
            }
        }

        return $data;
    }

    /**
     * Return the bucket values from the main aggregation
     *
     * @param QueryResponse $response ES Query response.
     *
     * @return AggregationValueInterface[]
     */
    private function getBucketValues(QueryResponse $response)
    {
        $bucket = $response->getAggregations()->getBucket('data');

        return $bucket !== null ? $bucket->getValues() : [];
    }
}
