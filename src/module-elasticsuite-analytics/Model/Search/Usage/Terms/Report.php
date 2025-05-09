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
namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms;

use Smile\ElasticsuiteAnalytics\Model\AbstractReport;
use Smile\ElasticsuiteAnalytics\Model\Report\SearchRequestBuilder;

/**
 * Search terms Report
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Report extends AbstractReport implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var array
     */
    private $postProcessors;

    /**
     * Constructor.
     *
     * @param \Magento\Search\Model\SearchEngine $searchEngine         Search engine.
     * @param SearchRequestBuilder               $searchRequestBuilder Search request builder.
     * @param array                              $postProcessors       Response post processors.
     */
    public function __construct(
        \Magento\Search\Model\SearchEngine $searchEngine,
        SearchRequestBuilder $searchRequestBuilder,
        array $postProcessors = []
    ) {
        parent::__construct($searchEngine, $searchRequestBuilder);
        $this->postProcessors = $postProcessors;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $data = [];

        foreach ($this->getValues($response) as $value) {
            $searchTerm = $value->getValue();
            if ($searchTerm !== '__other_docs') {
                $data[$searchTerm] = [
                    'term'            => $searchTerm,
                    'result_count'    => 0,
                    'sessions'        => round((int) $value->getMetrics()['unique_sessions'] ?: 0),
                    'visitors'        => round((int) $value->getMetrics()['unique_visitors'] ?: 0),
                    'conversion_rate' => number_format(0, 2),
                ];
                if (array_key_exists('result_count', $value->getMetrics())) {
                    $resultCountMetrics = $value->getMetrics()['result_count'];
                    if (is_array($resultCountMetrics)
                        && array_key_exists('result_count', $resultCountMetrics)
                        && array_key_exists('value', $resultCountMetrics['result_count'])
                    ) {
                        $resultCountMetrics = $resultCountMetrics['result_count']['value'] ?: 0;
                    }
                    $data[$searchTerm]['result_count'] = round((float) $resultCountMetrics ?: 0);
                }
            }
        }

        foreach ($this->postProcessors as $postProcessor) {
            $data = $postProcessor->postProcessResponse($data);
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
    private function getValues(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response)
    {
        $bucket = $response->getAggregations()->getBucket('search_terms');

        return $bucket !== null ? $bucket->getValues() : [];
    }
}
