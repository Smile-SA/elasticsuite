<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteSearch\Search
 * @author    David Dattée <david.dattee@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteSearch\Search;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Search\SearchEngineInterface;
use Magento\Framework\Search\SearchResponseBuilder;
use Magento\Search\Model\SearchEngine;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticSuiteCore\Search\Request\Builder;
use Smile\ElasticSuiteCore\Search\Request\Query\Builder as QueryBuilder;

/**
 * Substitution Search class for Magento\Search\Search
 *
 * @category Smile
 * @package  Smile\ElasticSuiteSearch\Search
 * @author   David Dattée <david.dattee@smile.fr>
 */
class Search implements SearchInterface
{
    /**
     * @var Builder
     */
    protected $requestBuilder;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var SearchEngine
     */
    protected $searchEngine;

    /**
     * @var SearchResponseBuilder
     */
    protected $searchResponseBuilder;

    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\ContainerConfigurationFactory
     */
    protected $containerConfigFactory;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var FilterQueryBuilder
     */
    protected $querybuilder;

    /**
     * SearchEs constructor.
     *
     * @param Builder                       $requestBuilder         Request Builder
     * @param ContainerConfigurationFactory $containerConfigFactory Config container
     * @param ScopeResolverInterface        $scopeResolver          Scope resolver
     * @param SearchEngineInterface         $searchEngine           Search engine
     * @param SearchResponseBuilder         $searchResponseBuilder  Search Response Builder
     * @param QueryBuilder                  $querybuilder           Query builder
     */
    public function __construct(
        Builder $requestBuilder,
        \Smile\ElasticSuiteCore\Search\Request\ContainerConfigurationFactory $containerConfigFactory,
        ScopeResolverInterface $scopeResolver,
        SearchEngineInterface $searchEngine,
        SearchResponseBuilder $searchResponseBuilder,
        QueryBuilder $querybuilder
    ) {
        $this->requestBuilder           = $requestBuilder;
        $this->scopeResolver            = $scopeResolver;
        $this->searchEngine             = $searchEngine;
        $this->searchResponseBuilder    = $searchResponseBuilder;
        $this->containerConfigFactory   = $containerConfigFactory;
        $this->querybuilder             = $querybuilder;
    }

    /**
     * Run the search
     *
     * @param SearchCriteriaInterface $searchCriteria Search criterias
     *
     * @return mixed
     */
    public function search(SearchCriteriaInterface $searchCriteria)
    {
        $scope = $this->scopeResolver->getScope();
        $request = $this->requestBuilder->create(
            $scope->getId(),
            $searchCriteria->getRequestName(),
            $searchCriteria->getCurrentPage() * $searchCriteria->getPageSize(),
            $searchCriteria->getPageSize(),
            $this->getQueryText($searchCriteria),
            (array) $searchCriteria->getSortOrders(),
            $this->extractFilters($searchCriteria)
        );
        $searchResponse = $this->searchEngine->search($request);

        return $this->searchResponseBuilder->build($searchResponse)->setSearchCriteria($searchCriteria);
    }

    /**
     * Extract filter from search criterias
     *
     * @param SearchCriteriaInterface $searchCriteria Search criteria
     *
     * @return array
     */
    private function extractFilters(SearchCriteriaInterface $searchCriteria)
    {
        $filters = [];
        foreach ($searchCriteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $criteriaFilter) {
                $filters[$criteriaFilter->getField()] = [$criteriaFilter->getValue()];
            }
        }

        return $filters;
    }

    /**
     * Get query text from searchCriterias
     *
     * @param SearchCriteriaInterface $searchCriteria Search criterias
     *
     * @return string
     */
    private function getQueryText(SearchCriteriaInterface $searchCriteria)
    {
        $queryText = '';
        foreach ($searchCriteria->getFilterGroups() as $group) {
            foreach ($group->getFilters() as $filter) {
                $queryText .= (strlen($queryText) > 0 ? ',' : '') . $filter->getValue();
            }
        }

        return $queryText;
    }
}
