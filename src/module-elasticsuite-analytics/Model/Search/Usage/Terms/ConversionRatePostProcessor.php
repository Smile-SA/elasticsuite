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

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Terms;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteAnalytics\Model\Report\QueryProviderInterface;

/**
 * Class ConversionRatePostProcessor
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class ConversionRatePostProcessor implements \Smile\ElasticsuiteAnalytics\Model\Report\PostProcessorInterface
{
    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $searchRequestBuilder;

    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Report\Context
     */
    private $context;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var QueryProviderInterface[]
     */
    private $queryProviders;

    /**
     * @var string
     */
    private $containerName;

    /**
     * ConversionRatePostProcessor constructor.
     *
     * @param \Magento\Search\Model\SearchEngine                                    $searchEngine         Search engine.
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder                        $searchRequestBuilder Search request builder.
     * @param \Smile\ElasticsuiteAnalytics\Model\Report\Context                     $context              Context.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory   Aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory             $queryFactory         Query factory.
     * @param array                                                                 $queryProviders       Query filters providers.
     * @param string                                                                $containerName        Container name.
     *
     * Warning: only works because there is a search request container with the same name as the index identifier
     */
    public function __construct(
        \Magento\Search\Model\SearchEngine $searchEngine,
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        \Smile\ElasticsuiteAnalytics\Model\Report\Context $context,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        array $queryProviders = [],
        $containerName = \Smile\ElasticsuiteTracker\Api\SessionIndexInterface::INDEX_IDENTIFIER
    ) {
        $this->searchEngine         = $searchEngine;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->context              = $context;
        $this->aggregationFactory   = $aggregationFactory;
        $this->queryFactory         = $queryFactory;
        $this->queryProviders       = $queryProviders;
        $this->containerName        = $containerName;
    }

    public function postProcessResponse($data)
    {
        $terms = array_keys($data);

        $storeId        = $this->context->getStoreId();
        $from           = 0;
        $size           = 0;
        $searchQuery    = $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'search_query.untouched', 'values' => $terms]);
        $queryFilters   = $this->getQueryFilters();

        $facets = [
            'search_terms' => $this->aggregationFactory->create(
                BucketInterface::TYPE_TERM,
                [
                    'childBuckets' => [
                        $this->aggregationFactory->create(
                            BucketInterface::TYPE_QUERY_GROUP,
                            [
                                'queries' => ['sales' => $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'product_sale'])],
                                'name' => 'conversion',
                            ]
                        )
                    ],
                    'size' => count($terms),
                    'field' => 'search_query.untouched',
                    'name' => 'search_terms',
                ]
            )
        ];

        $searchRequest  = $this->searchRequestBuilder->create($storeId, $this->containerName, $from, $size, $searchQuery, [], [], $queryFilters, $facets);
        $searchResponse = $this->searchEngine->search($searchRequest);

        foreach ($searchResponse->getAggregations()->getBucket('search_terms')->getValues() as $value) {
            $currentTerm  = $value->getValue();
            $sessionCount = $value->getMetrics()['count'];
            $salesCount   = 0;

            $conversionBucket = $value->getAggregations()->getBucket('conversion');
            if ($conversionBucket) {
                $salesCount = current($value->getAggregations()->getBucket('conversion')->getValues())->getMetrics()['count'];
            }
            if (isset($data[$currentTerm])) {
                $data[$currentTerm]['conversion_rate'] = $salesCount / $sessionCount;
            }
        }

        return $data;
    }

    private function getQueryFilters()
    {
        $queries = [];

        foreach ($this->queryProviders as $queryProvider) {
            $queries[] = $queryProvider->getQuery();
        }

        return array_filter($queries);
    }
}