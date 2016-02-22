<?php

namespace Smile\ElasticSuiteCore\Search\Request\Builder;


use Magento\Framework\Exception\StateException;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Cleaner
{


    /**
     * @var array
     */
    private $requestData;

    /**
     * @var array
     */
    private $mappedQueries;

    private $mappedAggregations;

    /**
     * Clean not binder queries and filters.
     *
     * @param array $requestData
     * @return array
     */
    public function clean(array $requestData)
    {
        $this->clear();
        $this->requestData = $requestData;
        $this->mapQueries();
        $this->mapAggregations();

        $requestData['query']  = $this->deferenceQueryField($requestData, 'query');
        $requestData['filter'] = $this->deferenceQueryField($requestData, 'filter');
        $requestData['aggregations'] = $this->mappedAggregations;

        /*$this->cleanQuery($requestData['query']);
        $this->cleanAggregations();
        $requestData = $this->requestData;
        $this->clear();*/

        return $requestData;
    }

    /**
     * Clear variables to default status.
     *
     * @return void
     */
    private function clear()
    {
        $this->mappedQueries = [];
        $this->requestData = [];
    }

    private function mapQueries()
    {
        // We just want queries without any placeholders left.
        $this->mappedQueries = array_filter($this->requestData['queries'], [$this, 'isFullyBinded']);

        foreach ($this->mappedQueries as &$query) {
            if ($query['type'] == QueryInterface::TYPE_BOOL) {
                $query['queries'] = $this->dereferenceBoolQueries($query);
                unset($query['query']);
            } elseif ($query['type'] == QueryInterface::TYPE_FILTER) {
                foreach (['filter', 'query'] as $currentField) {
                    if (isset($query[$currentField])) {
                        $query[$currentField] = &$this->deferenceQueryField($query, $currentField);
                    }
                }
            } elseif ($query['type'] == QueryInterface::TYPE_NESTED) {
                $query['query'] = &$this->deferenceQueryField($query, 'query');
            }
        }
    }

    private function mapAggregations()
    {
        $this->mappedAggregations = array_filter($this->requestData['aggregations'], [$this, 'isFullyBinded']);
    }

    private function &dereferenceBoolQueries($boolQuery)
    {
        $queries = [];

        foreach ($boolQuery['query'] as $referenceQuery) {
            $clause = $referenceQuery['clause'];
            $dereferencedQuery = &$this->dereferenceQuery($referenceQuery);
            if ($dereferencedQuery) {
                $queries[$clause][] = &$this->dereferenceQuery($referenceQuery);
            }
        }

        return $queries;
    }

    private function &deferenceQueryField($query, $field)
    {
        $dereferencedQuery = null;
        if (isset($query[$field])) {
            $referenceQuery = $query[$field];

            if (is_array($referenceQuery) && !isset($referenceQuery['reference'])) {
                $referenceQuery = current($referenceQuery);
            }

            if (is_array($referenceQuery) && isset($referenceQuery['reference'])) {
                $dereferencedQuery = &$this->dereferenceQuery($referenceQuery);
            }
        }
        return $dereferencedQuery;
    }

    private function &dereferenceQuery($query)
    {
        $dereferencedQuery = null;

        if (isset($this->mappedQueries[$query['reference']])) {
            return $this->mappedQueries[$query['reference']];
        }

        return $dereferencedQuery;
    }

    private function isFullyBinded($data)
    {
        $isFullyBinded = true;

        foreach ($data as $value) {
            if ($isFullyBinded) {
                if (is_array($value)) {
                    $isFullyBinded = $isFullyBinded && $this->isFullyBinded($value);
                } else if (preg_match('/^\$(.+)\$$/si', $value)) {
                    $isFullyBinded = false;
                }
            }
        }

        return $isFullyBinded;
    }
}
