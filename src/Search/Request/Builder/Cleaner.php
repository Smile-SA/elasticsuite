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

/**
 * Util used by the builder to clean search query arrays.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
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

    /**
     * @var array
     */
    private $mappedAggregations;

    /**
     * Clean not binded queries and filters.
     *
     * @param array $requestData Data to be cleansed.
     *
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
        $this->requestData   = [];
    }

    /**
     * Construct the mapped queries array :
     * - Keep only fully binded queries
     * - Build query hiearchy for complex queries (bool, filtered or nested)
     *
     * @return void
     */
    private function mapQueries()
    {
        // We just want queries without any placeholders left.
        $this->mappedQueries = array_filter($this->requestData['queries'], [$this, 'isFullyBinded']);

        /*
         * Dereference complex queries (bool, filtered and nested) to build children queries
         * before the parent one.
         */
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

    /**
     * Only keep fully binded aggregations
     *
     * @todo Complex aggregations handling and dereferecing
     *
     * @return void
     */
    private function mapAggregations()
    {
        $this->mappedAggregations = array_filter($this->requestData['aggregations'], [$this, 'isFullyBinded']);
    }

    /**
     * Unreference all clauses of a bool query.
     *
     * @param array $boolQuery Bool query configuration array.
     *
     * @return array
     */
    private function &dereferenceBoolQueries(array $boolQuery)
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

    /**
     * Dereference a query from a parent document.
     *
     * This methods return null if the query can not be dereferenced (non existing field
     * or query not mapped as root query).
     *
     * @param array  $query Parent query description array.
     * @param string $field Field containing the referenced query.
     *
     * @return array|null
     */
    private function &deferenceQueryField(array $query, $field)
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

    /**
     * Try to dereference a query.
     *
     * This methods return null if the query can not be dereferenced (query not mapped
     * as root query).
     *
     * @param array $query Query reference array.
     *
     * @return array|null
     */
    private function &dereferenceQuery(array $query)
    {
        $dereferencedQuery = null;

        if (isset($this->mappedQueries[$query['reference']])) {
            return $this->mappedQueries[$query['reference']];
        }

        return $dereferencedQuery;
    }

    /**
     * Check recursivelly if an array contains unbinded variables.
     *
     * @param array $data Data to be checked.
     *
     * @return boolean
     */
    private function isFullyBinded(array $data)
    {
        $isFullyBinded = true;

        foreach ($data as $value) {
            if ($isFullyBinded) {
                if (is_array($value)) {
                    $isFullyBinded = $isFullyBinded && $this->isFullyBinded($value);
                } elseif (preg_match('/^\$(.+)\$$/si', $value)) {
                    $isFullyBinded = false;
                }
            }
        }

        return $isFullyBinded;
    }
}
