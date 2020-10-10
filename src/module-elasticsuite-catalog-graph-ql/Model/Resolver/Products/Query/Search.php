<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\Query;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Search\Model\Search\PageSizeProvider;
use Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;

/**
 * Elasticsuite GraphQL Products Query Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Search implements ProductQueryInterface
{
    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var PageSizeProvider
     */
    private $pageSizeProvider;

    /**
     * @var ProductSearch
     */
    private $productsProvider;

    /**
     * @var FieldSelection
     */
    private $fieldSelection;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SearchInterface       $search                Search Engine
     * @param SearchResultFactory   $searchResultFactory   Search Results Factory
     * @param PageSizeProvider      $pageSize
     * @param FieldSelection        $fieldSelection        Field Selection
     * @param ProductSearch         $productsProvider       Product Provider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search Criteria Builder
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        PageSizeProvider $pageSize,
        FieldSelection $fieldSelection,
        ProductSearch $productsProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->search                = $search;
        $this->searchResultFactory   = $searchResultFactory;
        $this->pageSizeProvider      = $pageSize;
        $this->fieldSelection        = $fieldSelection;
        $this->productsProvider      = $productsProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(array $args, ResolveInfo $info, ContextInterface $context): SearchResult
    {
        $queryFields    = $this->fieldSelection->getProductsFieldSelection($info);
        $searchCriteria = $this->buildSearchCriteria($args, $info);

        $realPageSize = $searchCriteria->getPageSize();
        $realCurrentPage = $searchCriteria->getCurrentPage();
        //Because of limitations of sort and pagination on search API we will query all IDS
        $pageSize = $this->pageSizeProvider->getMaxPageSize();
        $searchCriteria->setPageSize($pageSize);
        $searchCriteria->setCurrentPage(0);
        $itemsResults = $this->search->search($searchCriteria);

        $providerSearchCriteria = clone($searchCriteria);
        // Pass a dummy search criteria (no filter) to product provider : filtering is already done.
        $providerSearchCriteria->setFilterGroups([]);
        //Address limitations of sort and pagination on search API apply original pagination from GQL query
        $providerSearchCriteria->setPageSize($realPageSize);
        $providerSearchCriteria->setCurrentPage($realCurrentPage);

        $searchResults = $this->productsProvider->getList($providerSearchCriteria, $itemsResults, $queryFields, $context);

        $totalPages = $realPageSize ? ((int)ceil($searchResults->getTotalCount() / $realPageSize)) : 0;
        $productArray    = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($searchResults->getItems() as $product) {
            $productArray[$product->getId()]          = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }

        return $this->searchResultFactory->create([
            'totalCount'           => $searchResults->getTotalCount(),
            'productsSearchResult' => $productArray,
            'searchAggregation'    => $itemsResults->getAggregations(),
            'pageSize'             => $realPageSize,
            'currentPage'          => $realCurrentPage,
            'totalPages'           => $totalPages,
        ]);
    }

    /**
     * Build search criteria from query input args
     *
     * @param array       $args Query Arguments
     * @param ResolveInfo $info Resolve Info
     *
     * @return SearchCriteriaInterface
     */
    private function buildSearchCriteria(array $args, ResolveInfo $info): SearchCriteriaInterface
    {
        $productFields       = (array) $info->getFieldSelection(1);
        $includeAggregations = isset($productFields['filters']) || isset($productFields['aggregations']);

        return $this->searchCriteriaBuilder->build($args, $includeAggregations);
    }
}
