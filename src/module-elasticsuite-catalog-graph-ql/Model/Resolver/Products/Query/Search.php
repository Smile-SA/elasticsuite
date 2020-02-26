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
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Search\Api\SearchInterface;

/**
 * Elasticsuite GraphQL Products Query Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Search
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
     * @param SearchInterface     $search              Search Engine
     * @param SearchResultFactory $searchResultFactory Search Results Factory
     * @param FieldSelection      $fieldSelection      Field Selection
     * @param ProductSearch       $productProvider     Product Provider
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        FieldSelection $fieldSelection,
        ProductSearch $productProvider
    ) {
        $this->search              = $search;
        $this->searchResultFactory = $searchResultFactory;
        $this->fieldSelection      = $fieldSelection;
        $this->productProvider     = $productProvider;
    }

    /**
     * Return results of full text catalog search of given term, and will return filtered results if filter is specified
     *
     * @param SearchCriteriaInterface $searchCriteria Search Criteria
     * @param ResolveInfo             $info           Resolve Info
     *
     * @return SearchResult
     * @throws \Exception
     */
    public function getResult(SearchCriteriaInterface $searchCriteria, ResolveInfo $info): SearchResult
    {
        $queryFields   = $this->fieldSelection->getProductsFieldSelection($info);
        $searchResults = $this->search->search($searchCriteria);

        // Pass a dummy search criteria (no filter) to product provider : filtering is already done.
        $providerSearchCriteria = clone($searchCriteria);
        $providerSearchCriteria->setFilterGroups([]);

        $productsResults = $this->productProvider->getList($providerSearchCriteria, $searchResults, $queryFields);
        $productArray    = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productsResults->getItems() as $product) {
            $productArray[$product->getId()]          = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }

        $maxPages = 0;
        if ($searchCriteria->getPageSize() && $searchCriteria->getPageSize() > 0) {
            $maxPages = (int) ceil($productsResults->getTotalCount() / $searchCriteria->getPageSize());
        }

        return $this->searchResultFactory->create([
            'totalCount'           => $searchResults->getTotalCount(),
            'productsSearchResult' => $productArray,
            'searchAggregation'    => $searchResults->getAggregations(),
            'pageSize'             => $searchCriteria->getPageSize(),
            'currentPage'          => $searchCriteria->getCurrentPage(),
            'totalPages'           => $maxPages,
        ]);
    }
}
