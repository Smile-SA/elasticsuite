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
     * Constructor.
     *
     * @param QueryBuilder     $queryBuilder     Adapter query builder
     * @param SortOrderBuilder $sortOrderBuilder Adapter sort orders builder
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->queryBuilder     = $queryBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
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
        $query = [
            'query'  => $this->getRootQuery($request),
            'filter' => $this->getRootFilter($request),
            'sort'   => $this->getSortOrders($request),
            'from'   => $request->getFrom(),
            'size'   => $request->getSize(),
        ];

        foreach ($request->getAggregation() as $currentAggregation) {
            $aggregationName = $currentAggregation->getName();
            $query['aggregations'][$aggregationName]['terms'] = [
                'field' => $currentAggregation->getField(),
            ];
        }

        return $query;
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
        return $this->queryBuilder->buildQuery($request->getQuery());
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
        return $this->queryBuilder->buildQuery($request->getFilter());
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
        return $this->sortOrderBuilder->buildSortOrders($request->getSortOrders());
    }
}
