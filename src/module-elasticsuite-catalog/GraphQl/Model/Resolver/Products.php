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

namespace Smile\ElasticsuiteCatalog\GraphQl\Model\Resolver;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Smile\ElasticsuiteCatalog\GraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Smile\ElasticsuiteCatalog\GraphQl\Model\Resolver\Products\ContextUpdater;
use Smile\ElasticsuiteCatalog\GraphQl\Model\Resolver\Products\Query\Search;

/**
 * Elasticsuite custom implementation of GraphQL Products Resolver
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 *
 * @deprecated Will be moved to a dedicated module.
 */
class Products implements ResolverInterface
{
    /**
     * @var Search
     */
    private $searchQuery;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchApiCriteriaBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalog\GraphQl\Model\Resolver\Products\ContextUpdater
     */
    private $contextUpdater;

    /**
     * @param Search                $searchQuery              Search Query
     * @param SearchCriteriaBuilder $searchApiCriteriaBuilder Search Api Criteria Builder
     * @param ContextUpdater        $contextUpdater           Context Updater
     */
    public function __construct(
        Search $searchQuery,
        SearchCriteriaBuilder $searchApiCriteriaBuilder,
        ContextUpdater $contextUpdater
    ) {
        $this->searchQuery              = $searchQuery;
        $this->searchApiCriteriaBuilder = $searchApiCriteriaBuilder;
        $this->contextUpdater           = $contextUpdater;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validateArgs($args);
        $this->contextUpdater->updateSearchContext($args);

        $searchCriteria = $this->searchApiCriteriaBuilder->build($args);
        $searchResult   = $this->searchQuery->getResult($searchCriteria, $info);

        // BC Comp for when $searchResult->getTotalPages() did not exist.
        $maxPages = 0;
        if ($searchCriteria->getPageSize() && $searchCriteria->getPageSize() > 0) {
            $maxPages = (int) ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        }

        return [
            'total_count'   => $searchResult->getTotalCount(),
            'items'         => $searchResult->getProductsSearchResult(),
            'page_info'     => [
                'page_size'    => $searchCriteria->getPageSize(),    // BC Comp for when $searchResult->getPageSize() did not exist.
                'current_page' => $searchCriteria->getCurrentPage(), // BC Comp for when $searchResult->getCurrentPage() did not exist.
                'total_pages'  => $maxPages,                         // BC Comp for when $searchResult->getTotalPages() did not exist.
            ],
            'search_result' => $searchResult,
            'layer_type'    => isset($args['search']) ? Resolver::CATALOG_LAYER_SEARCH : Resolver::CATALOG_LAYER_CATEGORY,
        ];
    }

    /**
     * Validate GraphQL query arguments and throw exception if needed.
     *
     * @param array $args GraphQL query arguments
     *
     * @throws GraphQlInputException
     */
    private function validateArgs(array $args)
    {
        if (!isset($args['search']) && !isset($args['filter'])) {
            throw new GraphQlInputException(
                __("'search' or 'filter' input argument is required.")
            );
        }
    }
}
