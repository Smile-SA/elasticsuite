<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
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
        'eq'       => 'values',
        'neq'      => 'values',
        'seq'      => 'values',
        'sneq'     => 'values',
        'in'       => 'values',
        'nin'      => 'values',
        'from'     => 'gte',
        'moreq'    => 'gte',
        'gteq'     => 'gte',
        'to'       => 'lte',
        'lteq'     => 'lte',
        'like'     => 'queryText',
        'fulltext' => 'queryText',
        'match'    => 'queryText',
        'in_set'   => 'values',
        // Trick to silently ignore that condition if it slips along with a price range here from advanced search.
        'currency' => 'currency',
    ];

    /**
     * @var array
     */
    private $rangeConditions = ['gt', 'gte', 'lt', 'lte'];

    /**
     * @var array
     */
    private $negativeConditions = ['neq', 'sneq', 'nin'];

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
     * @param string|null                     $currentPath     Current nested path or null.
     *
     * @return QueryInterface
     */
    public function create(ContainerConfigurationInterface $containerConfig, array $filters, $currentPath = null)
    {
        $queries = [];

        $mapping = $containerConfig->getMapping();

        foreach ($filters as $fieldName => $condition) {
            if ($condition instanceof QueryInterface) {
                $queries[] = $condition;
            } else {
                $mappingField = $mapping->getField($fieldName);
                $queries[]    = $this->prepareFieldCondition($mappingField, $condition, $currentPath);
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
     * @param FieldInterface $field       Filter field.
     * @param array|string   $condition   Filter condition.
     * @param string|null    $currentPath Current nested path or null.
     *
     * @return QueryInterface|null
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prepareFieldCondition(FieldInterface $field, $condition, $currentPath)
    {
        $queryType = QueryInterface::TYPE_TERMS;
        $condition = $this->prepareCondition($condition);

        if (count(array_intersect($this->rangeConditions, array_keys($condition))) >= 1) {
            $queryType = QueryInterface::TYPE_RANGE;
            $condition = ['bounds' => $condition];
        }

        $condition['field'] = $field->getMappingProperty(FieldInterface::ANALYZER_UNTOUCHED);

        if ($condition['field'] === null || isset($condition['queryText'])) {
            $analyzer = $field->getDefaultSearchAnalyzer();
            $property = $field->getMappingProperty($analyzer);
            if ($property) {
                $condition['field'] = $property;

                if (isset($condition['queryText'])) {
                    $queryType = QueryInterface::TYPE_MATCH;
                    $condition['minimumShouldMatch'] = '100%';
                }
            }
        }

        if (($queryType === QueryInterface::TYPE_TERMS)
            && ($field->getFilterLogicalOperator() === FieldInterface::FILTER_LOGICAL_OPERATOR_AND)
        ) {
            $query = $this->getCombinedTermsQuery($condition['field'], $condition['values']);
        } else {
            $query = $this->queryFactory->create($queryType, $condition);
        }

        if ($this->isNestedField($field, $currentPath)) {
            $queryParams = ['path' => $field->getNestedPath(), 'query' => $query];
            $query = $this->queryFactory->create(QueryInterface::TYPE_NESTED, $queryParams);
        }

        if (isset($condition['negative'])) {
            $query = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $query]);
        }

        return $query;
    }

    /**
     * @param FieldInterface $field       Filter field.
     * @param string|null    $currentPath Current nested path or null.
     *
     * @return bool
     * @throws \LogicException
     */
    private function isNestedField(FieldInterface $field, $currentPath)
    {
        $isNested = $field->isNested();

        if ($currentPath !== null) {
            if ($field->isNested() && ($field->getNestedPath() !== $currentPath)) {
                throw new \LogicException("Can not filter nested field {$field->getName()} with nested path $currentPath");
            }
            if (!$field->isNested()) {
                 throw new \LogicException("Can not filter non nested field {$field->getName()} in nested context ($currentPath)");
            }

            $isNested = false;
        }

        return $isNested;
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
            $condition = ['in' => [$condition]];
        }

        $conditionKeys = array_keys($condition);

        if (is_integer(current($conditionKeys))) {
            $condition = ['in' => $condition];
        }

        foreach ($condition as $key => $value) {
            if (!isset($this->mappedConditions[$key]) && !in_array($key, $this->rangeConditions)) {
                throw new \LogicException("Condition {$key} is not supported.");
            }

            if (isset($this->mappedConditions[$key])) {
                $condition[$this->mappedConditions[$key]] = $value;
                unset($condition[$key]);
            }

            if (in_array($key, $this->negativeConditions)) {
                $condition['negative'] = true;
            }
        }

        return $condition;
    }

    /**
     * Get a filter query corresponding to combining terms with a logical AND.
     *
     * @param string $field  Filter field.
     * @param array  $values Filter values.
     *
     * @return QueryInterface|null
     */
    private function getCombinedTermsQuery($field, $values)
    {
        $query = null;

        if (!is_array($values) && is_string($values)) {
            $values = explode(',', $values);
        } elseif (!is_array($values)) {
            $values = [$values];
        }

        $filters = [];
        foreach ($values as $value) {
            $filters[] = $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => $field, 'value' => $value]);
        }

        if (count($filters) === 1) {
            // Avoids using a boolean clause for a single term.
            $query = current($filters);
        } elseif (count($filters) > 0) {
            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => $filters]);
        }

        return $query;
    }
}
