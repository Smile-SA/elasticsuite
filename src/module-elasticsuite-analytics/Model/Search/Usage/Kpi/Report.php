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

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;

/**
 * Search usage KPI Report
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
        'sessions_count',
        'visitors_count',
        'search_page_views_count',
        'search_sessions_count',
        'search_usage_rate',
        'spellcheck_usage_count',
        'spellcheck_usage_rate',
    ];

    /**
     * Get data label.
     *
     * @param string $origin Origin code.
     * @return string
     */
    public function getLabel(string $origin): string
    {
        switch ($origin) {
            case 'product_views_catalog_category_view_count':
                return __('Category');
            case 'product_views_catalogsearch_result_index_count':
                return __('Search');
            case 'product_views_catalog_product_view_count':
                return __('Recommender');
            case 'product_views_catalogsearch_autocomplete_click_count':
                return __('Autocomplete');
            default:
                return __('Other');
        }
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = array_merge(array_fill_keys($this->defaultKeys, 0), ['page_views_count' => $response->count()]);

        foreach ($this->getBucketValues($response) as $value) {
            if ($value->getValue() == 'all') {
                $data['sessions_count'] = (int) $value->getMetrics()['unique_sessions'];
                $data['visitors_count'] = (int) $value->getMetrics()['unique_visitors'];
            } elseif ($value->getValue() == 'searches') {
                $data['search_page_views_count'] = (int) $value->getMetrics()['count'];
                $data['search_sessions_count']   = (int) $value->getMetrics()['unique_sessions'];
                $data['search_usage_rate']       = round($data['search_page_views_count'] / ($data['search_sessions_count'] ?: 1), 1);
                $data['spellcheck_usage_count']  = (int) $value->getMetrics()['spellcheck_usage']['sum'];
                $data['spellcheck_usage_rate']   = $value->getMetrics()['spellcheck_usage']['avg'];
            } elseif (in_array($value->getValue(), ['product_views', 'category_views', 'add_to_cart', 'sales'])) {
                $key = sprintf("%s_count", $value->getValue());
                $data[$key] = (int) $value->getMetrics()['count'];
                if ($value->getAggregations()->getBucket('origin')->getValues()) {
                    $originDetails = '';
                    $rawData = [];
                    foreach ($value->getAggregations()->getBucket('origin')->getValues() ?? [] as $originData) {
                        $key = sprintf("%s_%s_count", $value->getValue(), $originData->getValue());
                        $data[$key] = (int) $originData->getMetrics()['count'];
                        $label = $this->getLabel($key);
                        if (!array_key_exists($label, $rawData)) {
                            $rawData[$label] = $data[$key];
                        }
                    }
                    foreach ($rawData as $label => $count) {
                        $originDetails .= "• {$label}: {$count}\n";
                    }
                    $data[$value->getValue() . '_origin_details'] = $originDetails;
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
