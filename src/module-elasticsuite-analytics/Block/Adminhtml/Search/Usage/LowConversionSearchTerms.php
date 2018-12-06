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
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

class LowConversionSearchTerms extends PopularSearchTerms
{
    public function getTitle()
    {
        return __('Low conversion search terms');
    }

    public function getTermsData()
    {
        $termsData   = parent::getTermsData();
        $maxConvRate = $this->getMaxConversionRate();

        return array_filter($termsData, function($item) use ($maxConvRate) { return $item['conversion_rate'] < $maxConvRate; } );
    }

    private function getMaxConversionRate()
    {

        $storeId       = 1;
        $containerName = \Smile\ElasticsuiteTracker\Api\SessionIndexInterface::INDEX_IDENTIFIER;
        $from          = 0;
        $size          = 0;

        $facets = [
            'sales' => $this->aggregationFactory->create(
                 BucketInterface::TYPE_QUERY_GROUP,
                 [
                     'queries' => ['sales' => $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'product_sale'])],
                     'name' => 'conversion',
                 ]
            )
        ];

        $searchRequest  = $this->searchRequestBuilder->create($storeId, $containerName, $from, $size, null, [], [], [], $facets);
        $searchResponse = $this->searchEngine->search($searchRequest);

        $conversionBucket  = $searchResponse->getAggregations()->getBucket('conversion');
        $avgConversionRate = current($conversionBucket->getValues())->getMetrics()['count'] / (int) $searchResponse->count();

        return $avgConversionRate / 2;
    }
}
