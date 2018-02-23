<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Context;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder as FulltextQueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder as FilterQueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Builder for query part of the search request.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var FulltextQueryBuilder
     */
    private $fulltextQueryBuilder;

    /**
     * @var FilterQueryBuilder
     */
    private $filterQueryBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Context
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param QueryFactory         $queryFactory         Factory used to build subqueries.
     * @param FulltextQueryBuilder $fulltextQueryBuilder Builder of the fulltext query part.
     * @param FilterQueryBuilder   $filterQuerybuilder   Builder of the filters.
     * @param Context              $searchContext        Search Context.
     */
    public function __construct(
        QueryFactory $queryFactory,
        FulltextQueryBuilder $fulltextQueryBuilder,
        FilterQueryBuilder $filterQuerybuilder,
        Context $searchContext
    ) {
        $this->queryFactory         = $queryFactory;
        $this->fulltextQueryBuilder = $fulltextQueryBuilder;
        $this->filterQueryBuilder   = $filterQuerybuilder;
        $this->searchContext        = $searchContext;
    }


    /**
     * Create a filtered query with an optional fulltext query part.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param string|null|QueryInterface      $query                  Search query.
     * @param array                           $filters                Filter part of the query.
     * @param string                          $spellingType           For fulltext query : the type of spellchecked applied.
     *
     * @return QueryInterface
     */
    public function createQuery(ContainerConfigurationInterface $containerConfiguration, $query, array $filters, $spellingType)
    {
        $queryParams = [];

        if ($query) {
            if (is_object($query)) {
                $queryParams['query'] = $query;
            }
            if (is_string($query) || is_array($query)) {
                $queryParams['query'] = $this->createFulltextQuery($containerConfiguration, $query, $spellingType);
            }
        }

        foreach ($containerConfiguration->getFilters() as $filter) {
            $defaultFilterQuery = $filter->getFilterQuery($this->searchContext);
            if ($defaultFilterQuery !== null) {
                $filters[] = $filter->getFilterQuery($this->searchContext);
            }
        }

        if (!empty($filters)) {
            $queryParams['filter'] = $this->createFilterQuery($containerConfiguration, $filters);
        }

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
    }

    /**
     * Create a query from filters passed as arguments.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param array                           $filters                Filters used to build the query.
     *
     * @return QueryInterface
     */
    public function createFilterQuery(ContainerConfigurationInterface $containerConfiguration, array $filters)
    {
        return $this->filterQueryBuilder->create($containerConfiguration, $filters);
    }

    /**
     * Create a query from a search text query.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param string|null                     $queryText              Fulltext query.
     * @param string                          $spellingType           For fulltext query : the type of spellchecked applied.
     *
     * @return QueryInterface
     */
    public function createFulltextQuery(ContainerConfigurationInterface $containerConfiguration, $queryText, $spellingType)
    {
        return $this->fulltextQueryBuilder->create($containerConfiguration, $queryText, $spellingType);
    }

    /**
     * Create a query from filters passed as arguments.
     *
     * @deprecated
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param array                           $filters                Filters used to build the query.
     *
     * @return QueryInterface
     */
    public function createFilters(ContainerConfigurationInterface $containerConfiguration, array $filters)
    {
        return $this->createFilterQuery($containerConfiguration, $filters);
    }
}
