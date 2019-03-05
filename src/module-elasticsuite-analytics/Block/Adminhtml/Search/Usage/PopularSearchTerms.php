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
 * Class PopularSearchTerms
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @deprecated All actually used search terms report blocks are of type Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\SearchTerms
 *             with a dedicated/specific report model
 */
class PopularSearchTerms extends \Magento\Backend\Block\Template
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    protected $searchRequestBuilder;

    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    protected $searchEngine;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory
     */
    protected $aggregationFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory
     */
    protected $metricFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    protected $queryFactory;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $searchTermFactory;

    /**
     * PopularSearchTerms constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                               $context              Context.
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder                        $searchRequestBuilder Search request builder.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory $aggregationFactory   Bucket aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Aggregation\MetricFactory      $metricFactory        Metric aggregation factory.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory             $queryFactory         Query factory.
     * @param \Magento\Search\Model\QueryFactory                                    $searchTermFactory    Search term factory.
     * @param \Magento\Search\Model\SearchEngine                                    $searchEngine         Search engine.
     * @param array                                                                 $data                 Data.
     */
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

    /**
     * Get terms data from the report.
     *
     * @return mixed
     */
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

    /**
     * Add conversion rate to terms in the report
     *
     * @param mixed $data Terms report data
     *
     * @return mixed
     */
    private function addConversionRate($data)
    {
        $terms = array_keys($data);

        $storeId       = 1;
        $containerName = \Smile\ElasticsuiteTracker\Api\SessionIndexInterface::INDEX_IDENTIFIER;
        $searchQuery   = $this->queryFactory->create(
            QueryInterface::TYPE_TERMS,
            ['field' => 'search_query.untouched', 'values' => $terms]
        );
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
                                'queries' => [
                                    'sales' => $this->queryFactory->create(QueryInterface::TYPE_EXISTS, ['field' => 'product_sale']),
                                ],
                                'name' => 'conversion',
                            ]
                        ),
                    ],
                    'size' => count($terms),
                    'field' => 'search_query.untouched',
                    'name' => 'search_terms',
                ]
            ),
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

    /**
     * Get the merchandiser edit URL for a given search term.
     *
     * @param string $term Search term.
     *
     * @return string
     */
    private function getMerchandiserUrl($term)
    {
        $query = $this->searchTermFactory->create();
        $query->loadByQueryText($term);

        return $this->getUrl('search/term_merchandiser/edit', ['id' => $query->getId()]);
    }
}
