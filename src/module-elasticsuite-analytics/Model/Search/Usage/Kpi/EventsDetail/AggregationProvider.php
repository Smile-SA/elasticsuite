<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Search\Usage\Kpi\EventsDetail;

use Smile\ElasticsuiteAnalytics\Model\Report\AggregationProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationFactory;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Events/page view types detail KPI report aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class AggregationProvider implements AggregationProviderInterface
{
    /**
     * @var AggregationFactory
     */
    private $aggregationFactory;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * AggregationProvider constructor.
     *
     * @param AggregationFactory $aggregationFactory Aggregation factory.
     * @param QueryFactory       $queryFactory       Query factory.
     */
    public function __construct(
        AggregationFactory $aggregationFactory,
        QueryFactory $queryFactory
    ) {
        $this->aggregationFactory   = $aggregationFactory;
        $this->queryFactory         = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregation()
    {
        $aggParams = [
            'name'    => 'data',
            'queries' => $this->getQueries(),
        ];

        return $this->aggregationFactory->create(BucketInterface::TYPE_QUERY_GROUP, $aggParams);
    }

    /**
     * Return the queries of the query group aggregation.
     *
     * @return array
     */
    private function getQueries()
    {
        return [
            'all'      => $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                []
            ),
            'product_views' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalog_product_view',
                ]
            ),
            'category_views' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'catalog_category_view',
                ]
            ),
            'add_to_cart' => $this->queryFactory->create(
                QueryInterface::TYPE_EXISTS,
                [
                    'field' => 'page.cart.product_id',
                ]
            ),
            'sales' => $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'page.type.identifier',
                    'value' => 'checkout_onepage_success',
                ]
            ),
        ];
    }
}
