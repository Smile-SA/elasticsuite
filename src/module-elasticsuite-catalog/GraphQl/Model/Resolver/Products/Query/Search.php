<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\GraphQl\Model\Resolver\Products\Query;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Search\Api\SearchInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search as LegacyResolver;
use \Magento\CatalogGraphQl\Model\Layer\Context;

/**
 * Elasticsuite GraphQL Products Query Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 *
 * @deprecated Will be moved to a dedicated module.
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
     * @var LegacyResolver
     */
    private $legacyResolver;

    /**
     * @var Context
     */
    private $layerContext;

    /**
     * @param SearchInterface     $search              Search Engine
     * @param SearchResultFactory $searchResultFactory Search Results Factory
     * @param LegacyResolver      $legacyResolver      Legacy Product Search resolver
     * @param Context             $layerContext        Layer Context
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        LegacyResolver $legacyResolver,
        Context $layerContext
    ) {
        $this->search              = $search;
        $this->searchResultFactory = $searchResultFactory;
        $this->legacyResolver      = $legacyResolver;
        $this->layerContext        = $layerContext;
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
        $productProvider = $this->getProvider();
        if (null === $productProvider) { // BC comp for Magento < 2.3.4
            $searchResults = $this->search->search($searchCriteria);
            if ($searchResults->getAggregations()) {
                $this->layerContext->getCollectionProvider()->setSearchResults(
                    $searchResults->getAggregations(),
                    $searchResults->getTotalCount()
                );
            }

            return $this->legacyResolver->getResult($searchCriteria, $info);
        }

        $queryFields   = $this->getQueryFields($info);
        $searchResults = $this->search->search($searchCriteria);

        // Pass a dummy search criteria (no filter) to product provider : filtering is already done.
        $providerSearchCriteria = clone($searchCriteria);
        $providerSearchCriteria->setFilterGroups([]);

        $productsResults = $productProvider->getList($providerSearchCriteria, $searchResults, $queryFields);
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

    /**
     * Get Product Provider in a BC manner.
     * ProductSearch provider does not exist on Magento <2.3.4
     *
     * @return \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch|null
     */
    private function getProvider()
    {
        try {
            return ObjectManager::getInstance()->create(\Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch::class);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Get Query fields in a BC manner.
     * FieldSelection not injected in constructor because non existing in Magento <2.3.4
     *
     * @param ResolveInfo $info Resolver Info
     *
     * @return array
     */
    private function getQueryFields(ResolveInfo $info)
    {
        $fieldSelection = ObjectManager::getInstance()->create(\Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection::class);

        return $fieldSelection->getProductsFieldSelection($info);
    }
}
