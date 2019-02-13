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
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

/**
 * Class LowConversionSearchTerms
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @deprecated All actually used search terms report blocks are of type Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\SearchTerms
 *             with a dedicated/specific report model.
 */
class LowConversionSearchTerms extends PopularSearchTerms
{
    /**
     * Get block title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTitle()
    {
        return __('Low conversion search terms');
    }

    /**
     * Get terms report data
     *
     * @return array
     */
    public function getTermsData()
    {
        $termsData   = parent::getTermsData();
        $maxConvRate = $this->getMaxConversionRate();

        return array_filter(
            $termsData,
            function ($item) use ($maxConvRate) {
                return $item['conversion_rate'] < $maxConvRate;
            }
        );
    }

    /**
     * Get max conversion rate
     *
     * @return float
     */
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
            ),
        ];

        $searchRequest  = $this->searchRequestBuilder->create($storeId, $containerName, $from, $size, null, [], [], [], $facets);
        $searchResponse = $this->searchEngine->search($searchRequest);

        $conversionBucket  = $searchResponse->getAggregations()->getBucket('conversion');
        $avgConversionRate = current($conversionBucket->getValues())->getMetrics()['count'] / (int) $searchResponse->count();

        return $avgConversionRate / 2;
    }
}
