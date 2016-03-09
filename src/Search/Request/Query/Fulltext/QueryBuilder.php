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
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerConfigurationInterface;

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
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    public function create(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $queryParams = [
            'query'  => $this->getWeightedSearchQuery($containerConfig, $queryText),
            'filter' => $this->getCutoffFrequencyQuery($containerConfig, $queryText),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
    }

    /**
     * Provides a common search query for the searched text.
     *
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getCutoffFrequencyQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $queryParams = [
            'field'              => MappingInterface::DEFAULT_SEARCH_FIELD,
            'queryText'          => $queryText,
            'cutoffFrequency'    => $containerConfig->getRelevanceConfig()->getCutOffFrequency(),
            'minimumShouldMatch' => $containerConfig->getRelevanceConfig()->getMinimumShouldMatch(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_COMMON, $queryParams);
    }

    /**
     * Provides a weighted search query (multi match) using mapping field configuration.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param string                          $queryText       The text query.
     *
     * @return QueryInterface
     */
    private function getWeightedSearchQuery(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        $searchFields = [];

        $mapping = $containerConfig->getMapping();

        foreach ($mapping->getFields() as $field) {
            if ($field->isSearchable()) {
                $searchProperty = $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD);
                if ($searchProperty) {
                    $searchFields[$searchProperty] = $field->getSearchWeight();
                }
            }
        }

        $queryParams = [
            'fields'             => $searchFields,
            'queryText'          => $queryText,
            'minimumShouldMatch' => 1,
            'tieBreaker'         => $containerConfig->getRelevanceConfig()->getTieBreaker(),
        ];

        return $this->queryFactory->create(QueryInterface::TYPE_MULTIMATCH, $queryParams);
    }
}
