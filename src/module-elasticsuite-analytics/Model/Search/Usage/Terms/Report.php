<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms;

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;

class Report extends AbstractReport implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = [];

        foreach ($this->getValues($response) as $value) {
            $data[$value->getValue()] = [
                'term'            => $value->getValue(),
                'result_count'    => round($value->getMetrics()['result_count'] ?: 0),
                'sessions'        => round($value->getMetrics()['unique_sessions'] ?: 0),
                'visitors'        => round($value->getMetrics()['unique_visitors'] ?: 0),
                'conversion_rate' => number_format(0, 2),
            ];
        }

        return $data;
     }

    private function getValues(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $bucket = $response->getAggregations()->getBucket('search_terms');

        return $bucket !== null ? $bucket->getValues() : [];
    }
}
