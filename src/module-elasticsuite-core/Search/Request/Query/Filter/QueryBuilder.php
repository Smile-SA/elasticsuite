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

namespace Smile\ElasticsuiteCore\Search\Request\Query\Filter;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Prepare filter condition from an array as used into addFieldToFilter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryBuilder
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var array
     */
    private $mappedConditions = [
        'eq'     => 'values',
        'seq'    => 'values',
        'in'     => 'values',
        'from'   => 'gte',
        'moreq'  => 'gte',
        'gteq'   => 'gte',
        'to'     => 'lte',
        'lteq'   => 'lte',
        'like'   => 'queryText',
        'in_set' => 'values',
    ];

    /**
     * @var array
     */
    private $unsupportedConditions = [
        'nin',
        'notnull',
        'null',
        'finset',
        'regexp',
        'sneq',
        'neq',
    ];

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
     * Prepare filter condition from an array as used into addFieldToFilter.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param ContainerConfigurationInterface $containerConfig Search request container configuration.
     * @param array                           $filters         Filters to be built.
     *
     * @return QueryInterface
     */
    public function create(ContainerConfigurationInterface $containerConfig, array $filters)
    {
        $queries = [];

        $mapping = $containerConfig->getMapping();

        foreach ($filters as $fieldName => $condition) {
            if ($condition instanceof QueryInterface) {
                $queries[] = $condition;
            } else {
                $mappingField = $mapping->getField($fieldName);
                $queries[]    = $this->prepareFieldCondition($mappingField, $condition);
            }
        }

        $filterQuery = current($queries);

        if (count($queries) > 1) {
            $filterQuery = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => $queries]);
        }

        return $filterQuery;
    }

    /**
     * Transform the condition into a search request query object.
     *
     * @param FieldInterface $field     Filter field.
     * @param array|string   $condition Filter condition.
     *
     * @return QueryInterface
     */
    private function prepareFieldCondition(FieldInterface $field, $condition)
    {
        $queryType = QueryInterface::TYPE_TERMS;
        $condition = $this->prepareCondition($condition);

        if (count(array_intersect(['gt', 'gte', 'lt', 'lte'], array_keys($condition))) >= 1) {
            $queryType = QueryInterface::TYPE_RANGE;
            $condition = ['bounds' => $condition];
        }

        $condition['field'] = $field->getMappingProperty(FieldInterface::ANALYZER_UNTOUCHED);
        if ($condition['field'] === null) {
            $condition['field'] = $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD);
        }

        if (in_array('queryText', array_keys($condition))) {
            $queryType = QueryInterface::TYPE_MATCH;
            $condition['minimumShouldMatch'] = '100%';
        }

        $query = $this->queryFactory->create($queryType, $condition);

        if ($field->isNested()) {
            $queryParams = ['path' => $field->getNestedPath(), 'query' => $query];
            $query = $this->queryFactory->create(QueryInterface::TYPE_NESTED, $queryParams);
        }

        return $query;
    }

    /**
     * Ensure the condition is supported and try to tranform it into a supported type.
     *
     * @param array|integer|string $condition Parsed condition.
     *
     * @return array
     */
    private function prepareCondition($condition)
    {
        if (!is_array($condition)) {
            $condition = ['values' => [$condition]];
        }

        $conditionKeys = array_keys($condition);

        if (is_integer(current($conditionKeys))) {
            $condition = ['values' => $condition];
        }

        foreach ($condition as $key => $value) {
            if (in_array($key, $this->unsupportedConditions)) {
                throw new \LogicException("Condition {$key} is not supported.");
            }

            if (isset($this->mappedConditions[$key])) {
                $condition[$this->mappedConditions[$key]] = $value;
                unset($condition[$key]);
            }
        }

        return $condition;
    }
}
