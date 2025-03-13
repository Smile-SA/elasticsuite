<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder\Builder as SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation\Builder as AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Collapse\Builder as CollapseBuilder;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Map a search request into a ES Search query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @var CollapseBuilder
     */
    private $collapseBuilder;

    /**
     * Constructor.
     *
     * @param QueryBuilder       $queryBuilder       Adapter query builder.
     * @param SortOrderBuilder   $sortOrderBuilder   Adapter sort orders builder.
     * @param AggregationBuilder $aggregationBuilder Adapter aggregations builder.
     * @param CollapseBuilder    $collapseBuilder    Adapter collapse builder.
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder,
        AggregationBuilder $aggregationBuilder,
        CollapseBuilder $collapseBuilder
    ) {
        $this->queryBuilder       = $queryBuilder;
        $this->sortOrderBuilder   = $sortOrderBuilder;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->collapseBuilder    = $collapseBuilder;
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
            'size' => $request->getSize(),
        ];

        if ($searchRequest['size'] > 0) {
            $searchRequest['sort'] = $this->getSortOrders($request);
            $searchRequest['from'] = $request->getFrom();
        }

        $query = $this->getRootQuery($request);
        if ($query) {
            $searchRequest['query'] = $query;
        }

        $filter = $this->getRootFilter($request);
        if ($filter) {
            $searchRequest['post_filter'] = $filter;
        }

        $aggregations = $this->getAggregations($request);
        if (!empty($aggregations)) {
            $searchRequest['aggregations'] = $aggregations;
        }

        $searchRequest['track_total_hits'] = $request->getTrackTotalHits();

        if ((int) $request->getMinScore() > 0) {
            $searchRequest['min_score'] = $request->getMinScore();
        }

        $collapse = $this->getCollapse($request);
        if (!empty($collapse)) {
            $searchRequest['collapse'] = $collapse;
        }

        $source = $this->getSource($request);
        if (!empty($source)) {
            $searchRequest['_source'] = $source;
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

    /**
     * Extract and build collapse configuration of the search request
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getCollapse(RequestInterface $request)
    {
        $collapse = [];

        if ($request->hasCollapse()) {
            $collapse = $this->collapseBuilder->buildCollapse($request->getCollapse());
        }

        return $collapse;
    }

    /**
     * Extract the _source configuration from the search request.
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param RequestInterface $request Search request.
     *
     * @return array
     */
    private function getSource(RequestInterface $request)
    {
        $source = [];

        if ($request->hasSourceConfig()) {
            $sourceConfig = $request->getSourceConfig();
            if (array_key_exists('includes', $sourceConfig) || array_key_exists('excludes', $sourceConfig)) {
                $includes = $sourceConfig['includes'] ?? [];
                $excludes = $sourceConfig['excludes'] ?? [];
                if (!is_array($includes)) {
                    $includes = [$includes];
                }
                if (!is_array($excludes)) {
                    $excludes = [$excludes];
                }
                $source = array_filter([
                    'includes' => array_filter($includes, 'strlen'),
                    'excludes' => array_filter($excludes, 'strlen'),
                ]);
            } else {
                $source = $sourceConfig;
            }
        }

        return $source;
    }
}
