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

namespace Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;

/**
 * Custom Search Criteria builder for Product requests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchCriteriaBuilder extends \Magento\CatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder
{
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param Builder            $builder            Search Criteria Builder
     * @param FilterBuilder      $filterBuilder      Filter Builder
     * @param FilterGroupBuilder $filterGroupBuilder Filter Group Builder
     */
    public function __construct(
        Builder $builder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->filterBuilder      = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->builder            = $builder;
    }

    /**
     * {@inheritDoc}
     */
    public function build(array $args, bool $includeAggregation): SearchCriteriaInterface
    {
        $searchCriteria = $this->builder->build('products', $args);
        $isSearch       = !empty($args['search']);

        $requestName = 'catalog_view_container';
        if ($isSearch) {
            $this->addFilter($searchCriteria, 'search_term', $args['search']);
            $requestName = 'quick_search_container';
        }

        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchCriteria->setRequestName($requestName);

        return $searchCriteria;
    }

    /**
     * Add filter to search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria Search Criteria
     * @param string                  $field          Field
     * @param mixed                   $value          Value
     */
    private function addFilter(SearchCriteriaInterface $searchCriteria, string $field, $value): void
    {
        $filter = $this->filterBuilder
            ->setField($field)
            ->setValue($value)
            ->create();

        $this->filterGroupBuilder->addFilter($filter);
        $filterGroups   = $searchCriteria->getFilterGroups();
        $filterGroups[] = $this->filterGroupBuilder->create();
        $searchCriteria->setFilterGroups($filterGroups);
    }
}
