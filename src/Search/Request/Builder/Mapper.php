<?php

namespace Smile\ElasticSuiteCore\Search\Request\Builder;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\ObjectManagerInterface;

class Mapper
{
    const DEFAULT_BOOST = 1;

    private $requestData;
    private $objectManager;


    private $queryFactories = [
        QueryInterface::TYPE_BOOL   => 'Magento\Framework\Search\Request\Query\BoolExpression',
        QueryInterface::TYPE_FILTER => 'Smile\ElasticSuiteCore\Search\Request\Query\Filtered',
        QueryInterface::TYPE_NESTED => 'Smile\ElasticSuiteCore\Search\Request\Query\Nested',
        QueryInterface::TYPE_TERM   => 'Smile\ElasticSuiteCore\Search\Request\Query\Term',
        QueryInterface::TYPE_MATCH  => 'Smile\ElasticSuiteCore\Search\Request\Query\Match',
        QueryInterface::TYPE_TERMS  => 'Smile\ElasticSuiteCore\Search\Request\Query\Terms',
        QueryInterface::TYPE_RANGE  => 'Smile\ElasticSuiteCore\Search\Request\Query\Range',
    ];

    private $aggregationFactories = [
        BucketInterface::TYPE_TERM => 'Magento\Framework\Search\Request\Aggregation\TermBucket',
        //QueryInterface::TYPE_FILTER => 'Smile\ElasticSuiteCore\Search\Request\Query\Filtered'
    ];

    public function __construct(ObjectManagerInterface $objectManager, array $requestData)
    {
        $this->requestData   = $requestData;
        $this->objectManager = $objectManager;
    }

    public function getRootQuery()
    {
        $query = null;

        if (isset($this->requestData['query'])) {
            $query = $this->buildQuery($this->requestData['query']);
        }

        return $query;
    }

    public function getRootFilter()
    {
        $filter = null;

        if (isset($this->requestData['filter'])) {
            $query = $this->buildQuery($this->requestData['filter']);
        }

        return $query;
    }

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

    private function buildQuery($query)
    {
        $queryParams = $query;
        $queryClass  = false;
        $queryType   = $query['type'];

        if (isset($this->queryFactories[$queryType])) {
            $queryClass = $this->queryFactories[$queryType];
        }

        if ($queryType == QueryInterface::TYPE_BOOL) {
            $queryParams = ['name' => $query['name'], 'boost' => self::DEFAULT_BOOST];

            foreach ($query['queries'] as $clause => $clauseQueries) {
                foreach ($clauseQueries as $childrenQuery) {
                    $children = $this->buildQuery($childrenQuery);
                    if ($children) {
                        $queryParams[$clause][] = $children;
                    }
                }
            }
        } elseif ($queryType == QueryInterface::TYPE_FILTER) {
            $isValid = false;

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

        } elseif ($queryType == QueryInterface::TYPE_NESTED) {
            if (isset($queryParams['query']) && $queryParams['query']) {
                $queryParams['query'] = $this->buildQuery($queryParams['query']);
            } else {
                $queryParams = [];
            }
        }

        return $this->createQuery($queryClass, $queryParams);
    }


    private function buildAggregation($aggregation)
    {
        $aggregationParams = $aggregation;
        $aggregationType = $aggregation['type'];
        $aggregationClass = isset($this->aggregationFactories[$aggregationType]) ? $this->aggregationFactories[$aggregationType] : null;

        $aggregation = null;

        if ($aggregationClass) {
            if (!isset($aggregationParams['metrics'])) {
                $aggregationParams['metrics'] = [];
            }
            $aggregation = $this->objectManager->create($aggregationClass, $aggregationParams);
        }

        return $aggregation;
    }

    private function createQuery($queryClass, $queryParams) {
        $query = null;
        if ($queryClass && !empty($queryParams)) {
            $query = $this->objectManager->create($queryClass, $queryParams);
        }
        return $query;
    }
}
