<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticSuiteCore\Search\Request\Query\Fulltext\QueryBuilder as FulltextQueryBuilder;
use Smile\ElasticSuiteCore\Search\Request\Query\Filter\QueryBuilder as FilterQueryBuilder;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Builder for query part of the search request.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
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
     * Constructor.
     *
     * @param QueryFactory         $queryFactory         Factory used to build subqueries.
     * @param FulltextQueryBuilder $fulltextQueryBuilder Builder of the fulltext query part.
     * @param FilterQueryBuilder   $filterQuerybuilder   Buulder of the filters.
     */
    public function __construct(
        QueryFactory $queryFactory,
        FulltextQueryBuilder $fulltextQueryBuilder,
        FilterQueryBuilder $filterQuerybuilder
    ) {
        $this->queryFactory         = $queryFactory;
        $this->fulltextQueryBuilder = $fulltextQueryBuilder;
        $this->filterQueryBuilder   = $filterQuerybuilder;
    }


    /**
     * Create a filtered query with an optional fulltext query part.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param string|null                     $queryText              Fulltext query.
     * @param array                           $filters                Filter part of the query.
     *
     * @return QueryInterface
     */
    public function createQuery(ContainerConfigurationInterface $containerConfiguration, $queryText, array $filters)
    {
        $queryParams = ['filter' => $this->filterQueryBuilder->create($containerConfiguration, $filters)];

        if ($queryText) {
            $queryParams['query'] = $this->fulltextQueryBuilder->create($containerConfiguration, $queryText);
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
    public function createFilters(ContainerConfigurationInterface $containerConfiguration, array $filters)
    {
        return $this->filterQueryBuilder->create($containerConfiguration, $filters);
    }
}
