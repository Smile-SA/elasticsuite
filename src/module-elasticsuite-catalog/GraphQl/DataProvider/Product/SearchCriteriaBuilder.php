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

namespace Smile\ElasticsuiteCatalog\GraphQl\DataProvider\Product;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Custom Search Criteria builder for Product requests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchCriteriaBuilder
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
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $context;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @param Builder            $builder            Search Criteria Builder
     * @param FilterBuilder      $filterBuilder      Filter Builder
     * @param FilterGroupBuilder $filterGroupBuilder Filter Group Builder
     * @param ContextInterface   $context            Elasticsuite Context
     * @param QueryFactory       $queryFactory       Query Factory
     */
    public function __construct(
        Builder $builder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ContextInterface $context,
        QueryFactory $queryFactory
    ) {
        $this->filterBuilder      = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->builder            = $builder;
        $this->context            = $context;
        $this->queryFactory       = $queryFactory;
    }

    /**
     * Build search criteria
     *
     * @param array $args
     *
     * @return SearchCriteriaInterface
     */
    public function build(array $args): SearchCriteriaInterface
    {
        $this->updateSearchContext($args);

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
     * @param SearchCriteriaInterface $searchCriteria
     * @param string                  $field
     * @param mixed                   $value
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

    /**
     * Update search context according to current search.
     *
     * @param array $args GraphQL request arguments.
     */
    private function updateSearchContext($args)
    {
        if (!empty($args['search'])) {
            try {
                $query = $this->queryFactory->create()->loadByQueryText($args['search']);
            } catch (\Magento\Framework\Exception\LocalizedException $exception) {
                $query = $this->queryFactory->create();
            }

            $this->context->setCurrentSearchQuery($query);
        }
    }
}
