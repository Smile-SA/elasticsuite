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

namespace Smile\ElasticSuiteCore\Search\Request\Query\Fulltext;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory;

/**
 * Prepare a fulltext search query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryBuilder
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param QueryFactory $queryFactory Query factory (used to build subqueries.
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Create the fulltext search query.
     *
     * @param MappingInterface $mapping   Mapping to be searched.
     * @param string           $queryText The text query.
     *
     * @return QueryInterface
     */
    public function create(MappingInterface $mapping, $queryText)
    {
        $queryParams = [
            'query'  => $this->getWeightedSearchQuery($mapping, $queryText),
            'filter' => $this->getCutoffFrequencyQuery($queryText),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
    }

    /**
     * Provides a common search query for the searched text.
     *
     * @param string $queryText The text query.
     *
     * @return QueryInterface
     */
    private function getCutoffFrequencyQuery($queryText)
    {
        $queryParams = ['field' => MappingInterface::DEFAULT_SEARCH_FIELD, 'queryText' => $queryText];

        return $this->queryFactory->create(QueryInterface::TYPE_COMMON, $queryParams);
    }

    /**
     * Provides a weighted search query (multi match) using mapping field configuration.
     *
     * @param MappingInterface $mapping   Searched mapping.
     * @param string           $queryText The text query.
     *
     * @return QueryInterface
     */
    private function getWeightedSearchQuery(MappingInterface $mapping, $queryText)
    {
        $searchFields = [];

        foreach ($mapping->getFields() as $field) {
            if ($field->isSearchable()) {
                $searchProperty = $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD);
                if ($searchProperty) {
                    $searchFields[$searchProperty] = $field->getSearchWeight();
                }
            }
        }

        $queryParams = ['fields' => $searchFields, 'queryText' => $queryText];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }
}
