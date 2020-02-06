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

/**
 * Elasticsuite GraphQL Products Query Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
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
     * @var LegacyResolver
     */
    private $legacyResolver;

    /**
     * @param SearchInterface     $search              Search Engine
     * @param SearchResultFactory $searchResultFactory Search Results Factory
     * @param LegacyResolver      $legacyResolver      Legacy Product Search resolver
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        LegacyResolver $legacyResolver
    ) {
        $this->search              = $search;
        $this->searchResultFactory = $searchResultFactory;
        $this->legacyResolver      = $legacyResolver;
    }

    /**
     * Return results of full text catalog search of given term, and will return filtered results if filter is specified
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param ResolveInfo             $info
     *
     * @return SearchResult
     * @throws \Exception
     */
    public function getResult(SearchCriteriaInterface $searchCriteria, ResolveInfo $info): SearchResult
    {
        $productProvider = $this->getProvider();
        if (null === $productProvider) {
            return $this->legacyResolver->getResult($searchCriteria, $info);
        }

        $queryFields   = $this->getQueryFields($info);
        $itemsResults  = $this->search->search($searchCriteria);
        $searchResults = $productProvider->getList($searchCriteria, $itemsResults, $queryFields);
        $productArray  = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($searchResults->getItems() as $product) {
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
            'searchAggregation'    => $itemsResults->getAggregations(),
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
