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

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\ContextUpdater;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\Query\Search;

/**
 * Elasticsuite custom implementation of GraphQL Products Resolver
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Products implements ResolverInterface
{
    /**
     * @var ProductQueryInterface
     */
    private $searchQuery;

    /**
     * @var \Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\ContextUpdater
     */
    private $contextUpdater;

    /**
     * @param ProductQueryInterface $searchQuery    Search Query
     * @param ContextUpdater        $contextUpdater Context Updater
     */
    public function __construct(
        ProductQueryInterface $searchQuery,
        ContextUpdater $contextUpdater
    ) {
        $this->searchQuery    = $searchQuery;
        $this->contextUpdater = $contextUpdater;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $this->validateArgs($args);
        $this->contextUpdater->updateSearchContext($args);

        $searchResult = $this->searchQuery->getResult($args, $info, $context);
        $layerType    = Resolver::CATALOG_LAYER_CATEGORY;

        if (isset($args['search']) && (!empty($args['search']))) {
            $layerType = Resolver::CATALOG_LAYER_SEARCH;
        }

        return [
            'total_count'   => $searchResult->getTotalCount(),
            'items'         => $searchResult->getProductsSearchResult(),
            'suggestions'   => $searchResult->getSuggestions(),
            'page_info'     => [
                'page_size'    => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurrentPage(),
                'total_pages'  => $searchResult->getTotalPages(),
                'is_spellchecked' => $searchResult->isSpellchecked(),
                'query_id'     => $searchResult->getQueryId(),
            ],
            'search_result' => $searchResult,
            'layer_type'    => $layerType,
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
