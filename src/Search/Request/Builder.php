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

namespace Smile\ElasticSuiteCore\Search\Request;

use Magento\Framework\Search\Request\DimensionFactory;
use Smile\ElasticSuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticSuiteCore\Search\Request\SortOrder\SortOrderBuilder;
use Smile\ElasticSuiteCore\Search\Request\Aggregation\AggregationBuilder;
use Magento\Framework\Search\Request\BucketInterface;
use Smile\ElasticSuiteCore\Search\RequestInterface;
use Smile\ElasticSuiteCore\Search\RequestFactory;
use Magento\Framework\Search\Request\Dimension;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * ElasticSuite search requests builder.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var ContainerConfigurationFactory
     */
    private $containerConfigFactory;

    /**
     * @var string|null
     */
    private $queryText;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $from;

    /**
     * @var string
     */
    private $requestName;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $sortOrders = [];

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
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * Constructor.
     *
     * @param RequestFactory                $requestFactory         Factory used to build the search request.
     * @param DimensionFactory              $dimensionFactory       Factory used to dimensions of the search request.
     * @param QueryBuilder                  $queryBuilder           Builder for the query part of the search request.
     * @param SortOrderBuilder              $sortOrderBuilder       Builder for the sort part of the search request.
     * @param AggregationBuilder            $aggregationBuilder     Builder for the aggregation part of the search request.
     * @param ContainerConfigurationFactory $containerConfigFactory Search requests configuration.
     */
    public function __construct(
        RequestFactory $requestFactory,
        DimensionFactory $dimensionFactory,
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder,
        AggregationBuilder $aggregationBuilder,
        ContainerConfigurationFactory $containerConfigFactory
    ) {
        $this->requestFactory         = $requestFactory;
        $this->dimensionFactory       = $dimensionFactory;
        $this->queryBuilder           = $queryBuilder;
        $this->sortOrderBuilder       = $sortOrderBuilder;
        $this->aggregationBuilder     = $aggregationBuilder;
        $this->containerConfigFactory = $containerConfigFactory;
    }


    /**
     * Set request name
     *
     * @param string $requestName Request name.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setRequestName($requestName)
    {
        $this->requestName = $requestName;

        return $this;
    }

    /**
     * Set page size for the request.
     *
     * @param int $size Page size.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Set the search pagination offset.
     *
     * @param int $from Pagination offset
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Set the store id of the built search request.
     *
     * @param integer $storeId Store id.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * Add a new sort order to the request.
     *
     * @param string $field        Sort order name (reference to a sort order declared into the configuration).
     * @param string $direction    Sort order direction.
     * @param string $nestedPath   Nested path for nested field.
     * @param array  $nestedFilter Nested filter : optionaly used for nested field.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function addSortOrder($field, $direction, $nestedPath = null, $nestedFilter = null)
    {
        $this->sortOrders[$field] = ['direction' => strtolower($direction)];

        if ($nestedPath !== null) {
            $this->sortOrders[$field]['nestedPath'] = $nestedPath;

            if ($nestedFilter !== null) {
                $this->sortOrders[$field]['nestedFilter'] = $nestedFilter;
            }
        }

        return $this;
    }

    /**
     * Set fulltext query of the search request built.
     *
     * @param string $queryText Fultext query.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setQueryText($queryText)
    {
        $this->queryText = $queryText;

        return $this;
    }

    /**
     * Add a filter to the search request built.
     *
     * @param string $fieldName Filter field name.
     * @param array  $condition Filter condition.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function addFilter($fieldName, $condition)
    {
        $this->filters[$fieldName] = $condition;

        return $this;
    }

    /**
     * Create the search request object.
     *
     * @return RequestInterface
     */
    public function create()
    {
        $containerConfiguration = $this->getRequestContainerConfiguration();
        $mapping              = $containerConfiguration->getMapping();
        $facetFilters         = $this->getFacetFilters($mapping);
        $queryFilters         = array_diff_key($this->filters, $facetFilters);

        $requestParams = [
            'name'       => $this->requestName,
            'indexName'  => $containerConfiguration->getIndexName(),
            'type'       => $containerConfiguration->getTypeName(),
            'from'       => $this->from,
            'size'       => $this->size,
            'dimensions' => $this->buildDimensions(),
            'query'      => $this->queryBuilder->createQuery($mapping, $this->queryText, $queryFilters),
            'sortOrders' => $this->sortOrderBuilder->buildSordOrders($containerConfiguration, $this->sortOrders),
            'buckets'    => $this->aggregationBuilder->buildAggregations($containerConfiguration, $facetFilters),

        ];

        if (!empty($facetFilters)) {
            $requestParams['filter'] = $this->queryBuilder->createFilters($mapping, $facetFilters);
        }

        $request = $this->requestFactory->create($requestParams);

        return $request;
    }

    /**
     * Extract facet filters from current filters.
     *
     * @param MappingInterface $mapping Search mapping.
     *
     * @return array
     */
    private function getFacetFilters(MappingInterface $mapping)
    {
        $filters = [];

        foreach ($this->filters as $fieldName => $condition) {
            $field = $mapping->getField($fieldName);
            if ($field && $field->isFacet($this->requestName)) {
                $filters[$fieldName] = $condition;
            }
        }

        return $filters;
    }

    /**
     * Load the search request configuration (index, type, mapping, ...) using the search request container name.
     *
     * @throws \LogicException Thrown when the search container is not found into the configuration.
     *
     * @return ContainerConfigurationInterface
     */
    private function getRequestContainerConfiguration()
    {
        if ($this->requestName == null) {
            throw new \LogicException('Request name is not set');
        }

        $config = $this->containerConfigFactory->create(
            ['containerName' => $this->requestName, 'storeId' => $this->storeId]
        );

        if ($config == null) {
            throw new \LogicException("No configuration exists for request {$this->requestName}");
        }

        return $config;
    }

    /**
     * Build a dimenstion object from
     * It is quite useless since we have a per store index but required by the RequestInterface specification.
     *
     * @return Dimension[]
     */
    private function buildDimensions()
    {
        $dimensions = ['scope' => $this->dimensionFactory->create(['name' => 'scope', 'value' => $this->storeId])];

        return $dimensions;
    }
}
