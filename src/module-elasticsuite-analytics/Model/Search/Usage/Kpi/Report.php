<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi;

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;

class Report extends AbstractReport
{
    private $defaultKeys = ['page_view_counts', 'sessions_count', 'visitors_count', 'search_page_views_count', 'search_sessions_count', 'search_usage_rate'];

    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = array_merge(array_fill_keys($this->defaultKeys, 0), ['page_views_count' => $response->count()]);

        foreach ($this->getBucketValues($response) as $value) {
            if ($value->getValue() == 'all') {
                $data['sessions_count'] = (int) $value->getMetrics()['unique_sessions'];
                $data['visitors_count'] = (int) $value->getMetrics()['unique_visitors'];
            } else {
                $data['search_page_views_count'] = (int) $value->getMetrics()['count'];
                $data['search_sessions_count']   = (int) $value->getMetrics()['unique_sessions'];
                $data['search_usage_rate']       = round($data['search_page_views_count'] / ($data['search_sessions_count'] ?: 1), 1);
            }
        }

        return $data;
     }

     private function getBucketValues(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
     {
         $bucket = $response->getAggregations()->getBucket('data');

         return $bucket !== null ? $bucket->getValues() : [];
     }
}
