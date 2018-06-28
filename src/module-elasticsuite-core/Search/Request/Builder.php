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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request;

use Magento\Framework\Search\Request\DimensionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationBuilder;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteCore\Search\RequestFactory;
use Magento\Framework\Search\Request\Dimension;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;

/**
 * ElasticSuite search requests builder.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var ContainerConfigurationFactory
     */
    private $containerConfigFactory;

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
     * @var SpellcheckRequestFactory
     */
    private $spellcheckRequestFactory;

    /**
     * @var SpellcheckerInterface
     */
    private $spellchecker;

    /**
     * @var DimensionFactory
     */
    private $dimensionFactory;

    /**
     * Constructor.
     *
     * @param RequestFactory                $requestFactory           Factory used to build the request.
     * @param DimensionFactory              $dimensionFactory         Factory used to dimensions of the request.
     * @param QueryBuilder                  $queryBuilder             Builder for the query part of the request.
     * @param SortOrderBuilder              $sortOrderBuilder         Builder for the sort part of the request.
     * @param AggregationBuilder            $aggregationBuilder       Builder for the aggregation part of the request.
     * @param ContainerConfigurationFactory $containerConfigFactory   Search requests configuration.
     * @param SpellcheckRequestFactory      $spellcheckRequestFactory Spellchecking request factory.
     * @param SpellcheckerInterface         $spellchecker             Spellchecker.
     */
    public function __construct(
        RequestFactory $requestFactory,
        DimensionFactory $dimensionFactory,
        QueryBuilder $queryBuilder,
        SortOrderBuilder $sortOrderBuilder,
        AggregationBuilder $aggregationBuilder,
        ContainerConfigurationFactory $containerConfigFactory,
        SpellcheckRequestFactory $spellcheckRequestFactory,
        SpellcheckerInterface $spellchecker
    ) {
        $this->spellcheckRequestFactory = $spellcheckRequestFactory;
        $this->spellchecker             = $spellchecker;
        $this->requestFactory           = $requestFactory;
        $this->dimensionFactory         = $dimensionFactory;
        $this->queryBuilder             = $queryBuilder;
        $this->sortOrderBuilder         = $sortOrderBuilder;
        $this->aggregationBuilder       = $aggregationBuilder;
        $this->containerConfigFactory   = $containerConfigFactory;
    }

    /**
     * Create a new search request.
     *
     * @param integer               $storeId       Search request store id.
     * @param string                $containerName Search request name.
     * @param integer               $from          Search request pagination from clause.
     * @param integer               $size          Search request pagination size.
     * @param string|QueryInterface $query         Search request query.
     * @param array                 $sortOrders    Search request sort orders.
     * @param array                 $filters       Search request filters.
     * @param QueryInterface[]      $queryFilters  Search request filters prebuilt as QueryInterface.
     * @param array                 $facets        Search request facets.
     *
     * @return RequestInterface
     */
    public function create(
        $storeId,
        $containerName,
        $from,
        $size,
        $query = null,
        $sortOrders = [],
        $filters = [],
        $queryFilters = [],
        $facets = []
    ) {
        $containerConfig = $this->getRequestContainerConfiguration($storeId, $containerName);

        $facetFilters  = array_intersect_key($filters, $facets);
        $queryFilters  = array_merge($queryFilters, array_diff_key($filters, $facetFilters));

        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;

        if ($query && is_string($query)) {
            $spellingType = $this->getSpellingType($containerConfig, $query);
        }

        $requestParams = [
            'name'         => $containerName,
            'indexName'    => $containerConfig->getIndexName(),
            'type'         => $containerConfig->getTypeName(),
            'from'         => $from,
            'size'         => $size,
            'dimensions'   => $this->buildDimensions($storeId),
            'query'        => $this->queryBuilder->createQuery($containerConfig, $query, $queryFilters, $spellingType),
            'sortOrders'   => $this->sortOrderBuilder->buildSordOrders($containerConfig, $sortOrders),
            'buckets'      => $this->aggregationBuilder->buildAggregations($containerConfig, $facets, $facetFilters),
            'spellingType' => $spellingType,
        ];

        if (!empty($facetFilters)) {
            $requestParams['filter'] = $this->queryBuilder->createFilterQuery($containerConfig, $facetFilters);
        }

        $request = $this->requestFactory->create($requestParams);

        return $request;
    }

    /**
     * Retireve the spelling type for a fulltext query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration.
     * @param string|string[]                 $queryText       Query text.
     *
     * @return int
     */
    private function getSpellingType(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        if (is_array($queryText)) {
            $queryText = implode(" ", $queryText);
        }

        $spellcheckRequestParams = [
            'index'           => $containerConfig->getIndexName(),
            'type'            => $containerConfig->getTypeName(),
            'queryText'       => $queryText,
            'cutoffFrequency' => $containerConfig->getRelevanceConfig()->getCutOffFrequency(),
        ];

        $spellcheckRequest = $this->spellcheckRequestFactory->create($spellcheckRequestParams);
        $spellingType = $this->spellchecker->getSpellingType($spellcheckRequest);

        return $spellingType;
    }

    /**
     * Load the search request configuration (index, type, mapping, ...) using the search request container name.
     *
     * @throws \LogicException Thrown when the search container is not found into the configuration.
     *
     * @param integer $storeId       Store id.
     * @param string  $containerName Search request container name.
     *
     * @return ContainerConfigurationInterface
     */
    private function getRequestContainerConfiguration($storeId, $containerName)
    {
        if ($containerName === null) {
            throw new \LogicException('Request name is not set');
        }

        $config = $this->containerConfigFactory->create(
            ['containerName' => $containerName, 'storeId' => $storeId]
        );

        if ($config === null) {
            throw new \LogicException("No configuration exists for request {$containerName}");
        }

        return $config;
    }

    /**
     * Build a dimenstion object from
     * It is quite useless since we have a per store index but required by the RequestInterface specification.
     *
     * @param integer $storeId Store id.
     *
     * @return Dimension[]
     */
    private function buildDimensions($storeId)
    {
        $dimensions = ['scope' => $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId])];

        return $dimensions;
    }
}
