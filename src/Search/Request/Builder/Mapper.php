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

namespace Smile\ElasticSuiteCore\Search\Request\Builder;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Map an array to a request object.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Mapper
{
    /**
     * @var integer
     */
    const DEFAULT_BOOST = 1;

    /**
     * @var array
     */
    private $requestData;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $queryFactories = [
        QueryInterface::TYPE_BOOL   => 'Magento\Framework\Search\Request\Query\BoolExpression',
        QueryInterface::TYPE_FILTER => 'Smile\ElasticSuiteCore\Search\Request\Query\Filtered',
        QueryInterface::TYPE_NESTED => 'Smile\ElasticSuiteCore\Search\Request\Query\Nested',
        QueryInterface::TYPE_TERM   => 'Smile\ElasticSuiteCore\Search\Request\Query\Term',
        QueryInterface::TYPE_MATCH  => 'Smile\ElasticSuiteCore\Search\Request\Query\Match',
        QueryInterface::TYPE_TERMS  => 'Smile\ElasticSuiteCore\Search\Request\Query\Terms',
        QueryInterface::TYPE_RANGE  => 'Smile\ElasticSuiteCore\Search\Request\Query\Range',
    ];

    /**
     * @var array
     */
    private $sortOrderFactories = [
        SortOrderInterface::TYPE_STANDARD => 'Smile\ElasticSuiteCore\Search\Request\SortOrder\Standard',
        SortOrderInterface::TYPE_NESTED   => 'Smile\ElasticSuiteCore\Search\Request\SortOrder\Nested',
    ];

    /**
     * @var array
     */
    private $aggregationFactories = [
        BucketInterface::TYPE_TERM => 'Magento\Framework\Search\Request\Aggregation\TermBucket',
    ];

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param array                  $requestData   Request to be mapped.
     */
    public function __construct(ObjectManagerInterface $objectManager, array $requestData)
    {
        $this->requestData   = $requestData;
        $this->objectManager = $objectManager;
    }

    /**
     * Returns the root query of the request.
     *
     * @return QueryInterface
     */
    public function getRootQuery()
    {
        $query = null;

        if (isset($this->requestData['query'])) {
            $query = $this->buildQuery($this->requestData['query']);
        }

        return $query;
    }

    /**
     * Returns the root filter of the request.
     *
     * @return QueryInterface
     */
    public function getRootFilter()
    {
        $filter = null;

        if (isset($this->requestData['filter'])) {
            $filter = $this->buildQuery($this->requestData['filter']);
        }

        return $filter;
    }

    /**
     * Returns bucket of the request.
     *
     * @return BucketInterface[]
     */
    public function getAggregations()
    {
        $aggregations = [];

        if (isset($this->requestData['aggregations'])) {
            foreach ($this->requestData['aggregations'] as $currentAggregation) {
                $aggregation = $this->buildAggregation($currentAggregation);
                if ($aggregation) {
                    $aggregations[] = $aggregation;
                }
            }
        }

        return $aggregations;
    }

    /**
     * Build the sort orders clause of the query from the request data array.
     *
     * @return SortOrderInterface[]
     */
    public function getSortOrders()
    {
        $sortOrders = [];

        foreach ($this->requestData['sortOrders'] as $currentSortOrder) {
            if (isset($currentSortOrder['type']) && isset($this->sortOrderFactories[$currentSortOrder['type']])) {
                $sortOrderClass = $this->sortOrderFactories[$currentSortOrder['type']];

                if ($currentSortOrder['type'] == SortOrderInterface::TYPE_NESTED) {
                    $currentSortOrder['nestedFilter'] = $this->buildQuery($currentSortOrder['nestedFilter']);

                    if ($currentSortOrder['nestedFilter'] == null) {
                        $sortOrderClass = null;
                    }
                }

                if ($sortOrderClass !== null) {
                    $sortOrders[] = $this->objectManager->create($sortOrderClass, $currentSortOrder);
                }
            }
        }

        return $sortOrders;
    }

    /**
     * Transform a query array to a QueryInterface object.
     *
     * @param array $query Query array.
     *
     * @return QueryInterface
     */
    private function buildQuery(array $query)
    {
        $queryParams = $query;
        $queryClass  = false;
        $queryType   = $query['type'];

        if (isset($this->queryFactories[$queryType])) {
            $queryClass = $this->queryFactories[$queryType];
        }

        if ($queryType == QueryInterface::TYPE_BOOL) {
            $queryParams = $this->buildBoolQueryParams($query);
        } elseif ($queryType == QueryInterface::TYPE_FILTER) {
            $queryParams = $this->buildFilteredQueryParams($query);
        } elseif ($queryType == QueryInterface::TYPE_NESTED) {
            $queryParams = $this->buildNestedQueryParams($query);
        }

        return $this->createQuery($queryClass, $queryParams);
    }

    /**
     * Convert a bool query array into a bool QueryInterface constructor params.
     *
     * @param array $query Bool query array.
     *
     * @return array
     */
    private function buildBoolQueryParams(array $query)
    {
        $queryParams = ['name' => $query['name'], 'boost' => self::DEFAULT_BOOST];

        foreach ($query['queries'] as $clause => $clauseQueries) {
            foreach ($clauseQueries as $childrenQuery) {
                $children = $this->buildQuery($childrenQuery);
                if ($children) {
                    $queryParams[$clause][] = $children;
                }
            }
        }

        return $queryParams;
    }

    /**
     * Convert a filtered query array into a filtered QueryInterface constructor params.
     *
     * @param array $query Bool query array.
     *
     * @return array
     */
    private function buildFilteredQueryParams(array $query)
    {
        $queryParams = $query;
        $isValid     = false;

        if (isset($queryParams['filter']) && $queryParams['filter']) {
            $isValid = true;
            $queryParams['filter'] = $this->buildQuery($queryParams['filter']);
        }

        if (isset($queryParams['query']) && $queryParams['query']) {
            $isValid = true;
            $queryParams['query'] = $this->buildQuery($queryParams['query']);
        }

        if ($isValid == false) {
            $queryParams = [];
        }

        return $queryParams;
    }

    /**
     * Convert a nested query array into a nested QueryInterface constructor params.
     *
     * @param array $query Bool query array.
     *
     * @return array
     */
    private function buildNestedQueryParams(array $query)
    {
        $queryParams = [];

        if (isset($query['query']) && $query['query']) {
            $queryParams = array_merge($query, ['query' => $this->buildQuery($query['query'])]);
        };

        return $queryParams;
    }

    /**
     * Transform an aggregation array to a BucketInterface object.
     *
     * @param array $aggregation Aggregation definition array.
     *
     * @return BucketInterface
     */
    private function buildAggregation(array $aggregation)
    {
        $aggregationParams = $aggregation;
        $aggregationType = $aggregation['type'];

        $aggregationClass = null;

        if (isset($this->aggregationFactories[$aggregationType])) {
            $aggregationClass = $this->aggregationFactories[$aggregationType];
        }

        $aggregation = null;

        if ($aggregationClass) {
            if (!isset($aggregationParams['metrics'])) {
                $aggregationParams['metrics'] = [];
            }
            $aggregation = $this->objectManager->create($aggregationClass, $aggregationParams);
        }

        return $aggregation;
    }

    /**
     * Instanciate a new query.
     *
     * @param string $queryClass  Class of the query.
     * @param array  $queryParams Params passed to the query constructor.
     *
     * @return QueryInterface
     */
    private function createQuery($queryClass, $queryParams)
    {
        $query = null;
        if ($queryClass && !empty($queryParams)) {
            $query = $this->objectManager->create($queryClass, $queryParams);
        }

        return $query;
    }
}
