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

class PopularSearchTerms extends \Magento\Backend\Block\Template
{
    protected $searchRequestBuilder;

    protected $searchEngine;

    protected $aggregationFactory;

    protected $metricFactory;

    protected $queryFactory;

    private $searchTermFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteCore\Search\Request\Builder $searchRequestBuilder,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory,
        \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory $metricFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Magento\Search\Model\QueryFactory $searchTermFactory,
        \Magento\Search\Model\SearchEngine $searchEngine,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->aggregationFactory   = $aggregationFactory;
        $this->metricFactory        = $metricFactory;
        $this->queryFactory         = $queryFactory;
        $this->searchEngine         = $searchEngine;
        $this->searchTermFactory    = $searchTermFactory;
    }

    public function getTermsData()
    {
        $data = $this->getReport()->getData();

        try {
            foreach ($data as &$value) {
                $value['url'] = $this->getMerchandiserUrl($value['term']);
            }
        } catch (\Exception $e) {
            ;
        }

        if (!empty($data)) {
            $data = $this->addConversionRate($data);
        }

        return $data;
    }

    private function addConversionRate($data)
    {
        $terms = array_keys($data);

        $storeId       = 1;
        $containerName = \Smile\ElasticsuiteTracker\Api\SessionIndexInterface::INDEX_IDENTIFIER;
        $searchQuery   = $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'search_query.untouched', 'values' => $terms]);
        $from          = 0;
        $size          = 0;

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

        $searchRequest  = $this->searchRequestBuilder->create($storeId, $containerName, $from, $size, $searchQuery, [], [], [], $facets);
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

    private function getMerchandiserUrl($term)
    {
        $query = $this->searchTermFactory->create();
        $query->loadByQueryText($term);
        return $this->getUrl('search/term_merchandiser/edit', ['id' => $query->getId()]);
    }
}
