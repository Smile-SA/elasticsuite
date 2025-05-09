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
     * @var ProductSearch
     */
    private $productProvider;

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
     * @param FieldSelection        $fieldSelection        Field Selection
     * @param ProductSearch         $productProvider       Product Provider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search Criteria Builder
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        FieldSelection $fieldSelection,
        ProductSearch $productProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->search                = $search;
        $this->searchResultFactory   = $searchResultFactory;
        $this->fieldSelection        = $fieldSelection;
        $this->productProvider       = $productProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(array $args, ResolveInfo $info, ContextInterface $context): SearchResult
    {
        $queryFields    = $this->fieldSelection->getProductsFieldSelection($info);
        $searchCriteria = $this->buildSearchCriteria($args, $info);
        $searchResults  = $this->search->search($searchCriteria);

        // Pass a dummy search criteria (no filter) to product provider : filtering is already done.
        $providerSearchCriteria = clone($searchCriteria);
        $providerSearchCriteria->setFilterGroups([]);

        $productsResults = $this->productProvider->getList($providerSearchCriteria, $searchResults, $queryFields, $context);
        $productArray    = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productsResults->getItems() as $product) {
            $productArray[$product->getId()]          = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }

        $maxPages = 0;
        if ($searchCriteria->getPageSize() && $searchCriteria->getPageSize() > 0) {
            $maxPages = (int) ceil($searchResults->getTotalCount() / $searchCriteria->getPageSize());
        }

        return $this->searchResultFactory->create([
            'totalCount'           => $searchResults->getTotalCount(),
            'productsSearchResult' => $productArray,
            'searchAggregation'    => $searchResults->getAggregations(),
            'pageSize'             => $searchCriteria->getPageSize(),
            'currentPage'          => $searchCriteria->getCurrentPage(),
            'totalPages'           => $maxPages,
            'isSpellchecked'       => $searchResults->__toArray()['is_spellchecked'] ?? false,
            'queryId'              => $searchResults->__toArray()['query_id'] ?? null,
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
