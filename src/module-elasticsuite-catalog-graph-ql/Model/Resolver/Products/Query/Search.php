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
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Suggestions;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Smile\ElasticsuiteCore\Api\Search\SearchCriteriaInterface;
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
     * @var Suggestions
     */
    private $suggestions;

    /**
     * @param SearchInterface       $search                Search Engine
     * @param SearchResultFactory   $searchResultFactory   Search Results Factory
     * @param FieldSelection        $fieldSelection        Field Selection
     * @param ProductSearch         $productProvider       Product Provider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search Criteria Builder
     * @param Suggestions|null      $suggestions           Search Suggestions
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        FieldSelection $fieldSelection,
        ProductSearch $productProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ?Suggestions $suggestions = null
    ) {
        $this->search                = $search;
        $this->searchResultFactory   = $searchResultFactory;
        $this->fieldSelection        = $fieldSelection;
        $this->productProvider       = $productProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->suggestions           = $suggestions ?: ObjectManager::getInstance()->get(Suggestions::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(array $args, ResolveInfo $info, ContextInterface $context): SearchResult
    {
        $queryFields    = $this->fieldSelection->getProductsFieldSelection($info);
        $searchCriteria = $this->buildSearchCriteria($args, $info);
        // Do not apply track_total_hits to ensure best relevance.
        $searchCriteria->setTrackTotalHits(false);
        $searchResults  = $this->search->search($searchCriteria);

        $countCriteria = clone($searchCriteria);
        // Apply track_total_hits only for counting.
        $countCriteria->setTrackTotalHits(true);
        $countCriteria->setPageSize(0);
        $countResults  = $this->search->search($countCriteria);

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
            $maxPages = (int) ceil($countResults->getTotalCount() / $searchCriteria->getPageSize());
        }

        $suggestions = [];
        $totalCount = (int) $searchResults->getTotalCount();
        if ($totalCount === 0 && !empty($args['search'])) {
            $suggestions = $this->suggestions->execute($context, $args['search']);
        }

        return $this->searchResultFactory->create([
            'totalCount'           => $countResults->getTotalCount(),
            'productsSearchResult' => $productArray,
            'searchAggregation'    => $searchResults->getAggregations(),
            'pageSize'             => $searchCriteria->getPageSize(),
            'currentPage'          => $searchCriteria->getCurrentPage(),
            'totalPages'           => $maxPages,
            'isSpellchecked'       => $searchResults->__toArray()['is_spellchecked'] ?? false,
            'queryId'              => $searchResults->__toArray()['query_id'] ?? null,
            'suggestions'          => $suggestions,
        ]);
    }

    /**
     * Build search criteria from query input args
     *
     * @param array       $args Already processed query Arguments
     * @param ResolveInfo $info Resolve Info
     *
     * @return SearchCriteriaInterface
     * @throws LocalizedException
     */
    private function buildSearchCriteria(array $args, ResolveInfo $info): SearchCriteriaInterface
    {
        $productFields       = (array) $info->getFieldSelection(1);
        $includeAggregations = isset($productFields['filters']) || isset($productFields['aggregations']);

        return $this->searchCriteriaBuilder->build($args, $includeAggregations);
    }
}
