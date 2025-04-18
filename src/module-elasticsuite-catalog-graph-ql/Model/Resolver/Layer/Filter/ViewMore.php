<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Layer\Filter;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Smile\ElasticsuiteCatalogGraphQl\Model\Resolver\Products\ContextUpdater;
use \Smile\ElasticsuiteCatalogGraphQl\Model\Layer\Filter\ViewMore\Context as ViewMoreContext;

/**
 * ViewMore resolver. Used when fetching all values of a specific filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ViewMore implements ResolverInterface
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
     * @var \Smile\ElasticsuiteCatalogGraphQl\Model\Layer\Filter\ViewMore\Context
     */
    private $viewMoreContext;

    /**
     * @param ProductQueryInterface $searchQuery     Search Query
     * @param ContextUpdater        $contextUpdater  Context Updater
     * @param ViewMoreContext       $viewMoreContext View More Context
     */
    public function __construct(
        ProductQueryInterface $searchQuery,
        ContextUpdater $contextUpdater,
        ViewMoreContext $viewMoreContext
    ) {
        $this->searchQuery     = $searchQuery;
        $this->contextUpdater  = $contextUpdater;
        $this->viewMoreContext = $viewMoreContext;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $this->validateArgs($args);
        $this->viewMoreContext->setFilterName($args['filterName']);

        $this->contextUpdater->updateSearchContext($args);

        $args['currentPage'] = 0;
        $args['pageSize']    = 0;

        $searchResult = $this->searchQuery->getResult($args, $info, $context);
        $layerType    = Resolver::CATALOG_LAYER_CATEGORY;

        if (isset($args['search']) && (!empty($args['search']))) {
            $layerType = Resolver::CATALOG_LAYER_SEARCH;
        }

        return [
            'total_count'   => $searchResult->getTotalCount(),
            'items'         => $searchResult->getProductsSearchResult(),
            'page_info'     => [
                'page_size'    => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurrentPage(),
                'total_pages'  => $searchResult->getTotalPages(),
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
        if (!isset($args['filterName'])) {
            throw new GraphQlInputException(
                __("'filterName' input argument is required.")
            );
        }
    }
}
