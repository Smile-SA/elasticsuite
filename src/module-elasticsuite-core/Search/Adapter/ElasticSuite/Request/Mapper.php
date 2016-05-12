<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request;

use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\SortOrder\Builder as SortOrderBuilder;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Aggregation\Builder as AggregationBuilder;
use Smile\ElasticSuiteCore\Search\RequestInterface;

/**
 * Map a search request into a ES Search query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Mapper
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var AggregationBuilder
     */
    private $aggregationBuilder;

    /**
     * Constructor.
     *
     * @param QueryBuilder       $queryBuilder       Adapter query builder.
     * @param SortOrderBuilder   $sortOrderBuilder   Adapter sort orders builder.
     * @param AggregationBuilder $aggregationBuilder Adapter aggregations builder.
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder,
        AggregationBuilder $aggregationBuilder
    ) {
        $this->queryBuilder       = $queryBuilder;
        $this->sortOrderBuilder   = $sortOrderBuilder;
        $this->aggregationBuilder = $aggregationBuilder;
    }

    /**
     * Transform the search request into an ES request.
     *
     * @param RequestInterface $request Search Request.
     *
     * @return array
     */
    public function buildSearchRequest(RequestInterface $request)
    {
        $searchRequest = [
            'from'         => $request->getFrom(),
            'size'         => $request->getSize(),
            'sort'         => $this->getSortOrders($request),
        ];

        $query = $this->getRootQuery($request);
        if ($query) {
            $searchRequest['query'] = $query;
        }

        $filter = $this->getRootFilter($request);

        if ($filter) {
            $searchRequest['filter'] = $filter;
        }

        $aggregations = $this->getAggregations($request);
        if (!empty($aggregations)) {
            $searchRequest['aggregations'] = $aggregations;
        }

        return $searchRequest;
    }


    /**
     * Extract and build the root query of the search request.
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getRootQuery(RequestInterface $request)
    {
        $query = null;

        if ($request->getQuery()) {
            $query = $this->queryBuilder->buildQuery($request->getQuery());
        }

        return $query;
    }

    /**
     * Extract and build the root filter of the search request.
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getRootFilter(RequestInterface $request)
    {
        $filter = null;

        if ($request->getFilter()) {
            $filter = $this->queryBuilder->buildQuery($request->getFilter());
        }

        return $filter;
    }

    /**
     * Extract and build sort orders of the search request.
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getSortOrders(RequestInterface $request)
    {
        $sortOrders = [];

        if ($request->getSortOrders()) {
            $sortOrders = $this->sortOrderBuilder->buildSortOrders($request->getSortOrders());
        }

        return $sortOrders;
    }

    /**
     * Extract and build aggregations of the search request.
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getAggregations(RequestInterface $request)
    {
        $aggregations = [];

        if ($request->getAggregation()) {
            $aggregations = $this->aggregationBuilder->buildAggregations($request->getAggregation());
        }

        return $aggregations;
    }
}
