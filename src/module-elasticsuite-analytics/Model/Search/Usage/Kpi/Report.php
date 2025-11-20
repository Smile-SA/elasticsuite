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

use Magento\Framework\Api\Search\AggregationValueInterface;
use Smile\ElasticsuiteAnalytics\Model\AbstractReport;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;

/**
 * Search usage KPI Report.
 * Provides number of search page views, sessions with searches and number of searches per session.
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
        'search_page_views_count',
        'search_sessions_count',
        'search_usage_rate',
    ];

    /**
     * {@inheritdoc}
     */
    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = array_merge(array_fill_keys($this->defaultKeys, 0), ['search_page_views_count' => $response->count()]);

        $uniqueSessions = $this->getBucketValues($response, 'unique_sessions');
        if (!empty($uniqueSessions)) {
            $uniqueSessions = current($uniqueSessions);
            $data['search_sessions_count'] = (int) $uniqueSessions->getMetrics()['value'];
            $data['search_usage_rate']     = round($data['search_page_views_count'] / ($data['search_sessions_count'] ?: 1), 1);
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
