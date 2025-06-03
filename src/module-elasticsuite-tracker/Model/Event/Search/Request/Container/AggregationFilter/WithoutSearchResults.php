<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Search\Request\Container\AggregationFilter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Aggregation filter to limit aggregated to those of pages with 0 result
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class WithoutSearchResults implements FilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * WithoutSearchResults constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory Query Factory.
     */
    public function __construct(\Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterQuery()
    {
        $query = $this->queryFactory->create(
            QueryInterface::TYPE_BOOL,
            [
                'must' => [
                    $this->getNoResultsQuery(),
                    $this->getIsFirstPageQuery(),
                ],
                'mustNot' => [
                    $this->hasNavigationFiltersQuery(),
                ],
            ]
        );

        return $query;
    }

    /**
     * Return "no products" query.
     *
     * @return QueryInterface
     */
    protected function getNoResultsQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['field' => 'page.product_list.product_count', 'value' => 0]
        );
    }

    /**
     * Return "only first page" query.
     *
     * @return QueryInterface
     */
    protected function getIsFirstPageQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            [
                'field' => 'page.product_list.current_page',
                'value' => 1,
            ]
        );
    }

    /**
     * Return query indicating there are active product list filters.
     *
     * @return QueryInterface
     */
    protected function hasNavigationFiltersQuery()
    {
        return $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path'  => 'page.product_list.filters',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_EXISTS,
                    ['field' => 'page.product_list.filters']
                ),
            ]
        );
    }
}
