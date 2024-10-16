<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;

use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build a search query from a search engine rule product condition.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class QueryBuilder
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var AttributeList
     */
    private $attributeList;

    /**
     * @var SpecialAttributesProvider
     */
    private $specialAttributesProvider;

    /**
     * @var NestedFilterInterface[]
     */
    private $nestedFilters;

    /**
     * Constructor.
     *
     * @param AttributeList             $attributeList             Search rule product attributes list
     * @param QueryFactory              $queryFactory              Search query factory.
     * @param SpecialAttributesProvider $specialAttributesProvider Special Attributes Provider.
     * @param NestedFilterInterface[]   $nestedFilters             Filters applied to nested fields during query building.
     */
    public function __construct(
        AttributeList $attributeList,
        QueryFactory $queryFactory,
        SpecialAttributesProvider $specialAttributesProvider,
        $nestedFilters = []
    ) {
        $this->queryFactory              = $queryFactory;
        $this->attributeList             = $attributeList;
        $this->specialAttributesProvider = $specialAttributesProvider;
        $this->nestedFilters             = $nestedFilters;
    }

    /**
     * Build the query for a condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return QueryInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSearchQuery(ProductCondition $productCondition)
    {
        $query = null;

        $query = $this->getSpecialAttributesSearchQuery($productCondition);

        $conditionValue = $productCondition->getValue();
        $conditionValue = array_filter(is_array($conditionValue) ? $conditionValue : [$conditionValue], 'strlen');
        if ($query === null && (!empty($conditionValue) || $productCondition->getOperator() === '<=>')) {
            $this->prepareFieldValue($productCondition);
            $queryType   = QueryInterface::TYPE_TERMS;
            $queryParams = $this->getTermsQueryParams($productCondition);

            if ($productCondition->getInputType() === 'string' && !in_array($productCondition->getOperator(), ['()', '!()', '<=>'])) {
                $queryType   = QueryInterface::TYPE_MATCH;
                $queryParams = $this->getMatchQueryParams($productCondition);
            } elseif (in_array($productCondition->getOperator(), ['>=', '>', '<=', '<'])) {
                $queryType   = QueryInterface::TYPE_RANGE;
                $queryParams = $this->getRangeQueryParams($productCondition);
            } elseif ($productCondition->getOperator() === '<=>') {
                $queryType  = QueryInterface::TYPE_MISSING;
                $queryParams = ['field' => $this->getSearchFieldName($productCondition)];
            }

            $query = $this->prepareQuery($queryType, $queryParams)->setName($productCondition->asString());

            if (substr($productCondition->getOperator(), 0, 1) === '!') {
                $query = $this->applyNegation($query);
            }

            $field = $this->getSearchField($productCondition);

            if ($field->isNested()) {
                $nestedPath        = $field->getNestedPath();
                $nestedQueryParams = ['query' => $query, 'path' => $nestedPath];

                if (isset($this->nestedFilters[$nestedPath])) {
                    $nestedFilterClauses           = [];
                    $nestedFilterClauses['must'][] = $this->nestedFilters[$nestedPath]->getFilter();
                    $nestedFilterClauses['must'][] = $nestedQueryParams['query'];

                    $nestedFilter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $nestedFilterClauses);

                    $nestedQueryParams['query'] = $nestedFilter;
                }

                $query = $this->queryFactory->create(QueryInterface::TYPE_NESTED, $nestedQueryParams);
                $query->setName($productCondition->asString());
            }
        }

        return $query;
    }

    /**
     * Create a query for special attribute.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return QueryInterface|null
     */
    private function getSpecialAttributesSearchQuery(ProductCondition $productCondition)
    {
        $query = null;

        if ($productCondition->getAttribute() === 'sku') {
            $queryParams = [];
            $fieldName   = $this->getSearchFieldName($productCondition);

            $clause = (substr($productCondition->getOperator(), 0, 1) === '!') ? 'mustNot' : 'should';
            // SKU values can be an array of value, due to the picker.
            foreach (explode(',', $productCondition->getValue()) as $value) {
                // Instead of just a "should" clause, we will have "should" or "must_not" depending on the rule operator.
                $queryParams[$clause][] = $this->prepareQuery(
                    QueryInterface::TYPE_MATCH,
                    ['field' => $fieldName, 'queryText' => trim($value), 'minimumShouldMatch' => "100%"]
                );
            }

            // One clause must match between all.
            $queryParams['minimumShouldMatch'] = 1;
            $query = $this->prepareQuery(QueryInterface::TYPE_BOOL, $queryParams);
        }

        if (in_array($productCondition->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $specialAttribute = $this->specialAttributesProvider->getAttribute($productCondition->getAttribute());
            $query            = $specialAttribute->getSearchQuery($productCondition);
        }

        return $query;
    }

    /**
     * Retrieve params for a match query from condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return array
     */
    private function getMatchQueryParams(ProductCondition $productCondition)
    {
        $fieldName          = $this->getSearchFieldName($productCondition);
        $queryText          = $productCondition->getValue();
        $minimumShouldMatch = "100%";

        return [
            'field'              => $fieldName,
            'queryText'          => $queryText,
            'minimumShouldMatch' => $minimumShouldMatch,
        ];
    }

    /**
     * Retrieve params for a range query from condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return array
     */
    private function getRangeQueryParams(ProductCondition $productCondition)
    {
        $fieldName = $this->getSearchFieldName($productCondition);
        $inputType = $productCondition->getInputType();
        $operator  = $productCondition->getOperator();
        $value     = ($inputType == 'date') ? $productCondition->getValue() : (float) $productCondition->getValue();

        switch ($operator) {
            case '>':
                $operator = 'gt';
                break;
            case '>=':
                $operator = 'gte';
                break;
            case '<':
                $operator = 'lt';
                break;
            case '<=':
                $operator = 'lte';
                break;
        }

        return ['bounds' => [$operator => $value], 'field' => $fieldName];
    }

    /**
     * Retrieve params for a terms query from condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return array
     */
    private function getTermsQueryParams(ProductCondition $productCondition)
    {
        $field     = $this->getSearchField($productCondition);
        $fieldName = $this->getSearchFieldName($productCondition);
        $values    = $productCondition->getValue();

        if (!is_array($values) && in_array($productCondition->getOperator(), ['()', '!()'])) {
            $values = explode(',', $values);
        }

        if ($field->getType() == FieldInterface::FIELD_TYPE_BOOLEAN) {
            $values = (bool) $values;
        }

        return ['field' => $fieldName, 'values' => $values];
    }

    /**
     * Instantiate query from type and params.
     *
     * @param string $queryType   Query type.
     * @param array  $queryParams Query instantiation params.
     *
     * @return QueryInterface
     */
    private function prepareQuery($queryType, $queryParams)
    {
        return $this->queryFactory->create($queryType, $queryParams);
    }

    /**
     * Apply a negation to the current query.
     *
     * @param QueryInterface $query Negated query.
     *
     * @return QueryInterface
     */
    private function applyNegation(QueryInterface $query)
    {
        return $this->prepareQuery(QueryInterface::TYPE_NOT, ['query' => $query]);
    }

    /**
     * Retrieve ES mapping field for the current condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    private function getSearchField(ProductCondition $productCondition)
    {
        $field = $this->attributeList->getField($productCondition->getAttribute());

        return $field;
    }

    /**
     * Retrieve ES mapping field name used for the current condition (including analyzer).
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return string
     */
    private function getSearchFieldName(ProductCondition $productCondition)
    {
        $attributeName = $productCondition->getAttribute();

        $field    = $this->attributeList->getField($attributeName);
        $analyzer = FieldInterface::ANALYZER_UNTOUCHED;

        // "sku" can support both "contains" : will use the default search analyzer, and "is one of" that will use the untouched.
        if (in_array($productCondition->getInputType(), ["string", "sku"]) && !in_array($productCondition->getOperator(), ['()', '!()'])) {
            $analyzer = $field->getDefaultSearchAnalyzer();
        }

        // If the field is "used_for_promo_rules" but not "searchable", $field->getMappingProperty() might return null.
        // In this case, we fallback to raw field name, that should exist in mapping.
        return $field->getMappingProperty($analyzer) ?? $field->getName();
    }

    /**
     * Update the condition value to ignore empty array items.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return $this
     */
    private function prepareFieldValue(ProductCondition $productCondition)
    {
        $value = $productCondition->getValue();

        if (is_array($value)) {
            // The call to array_values ensures the array keys are re-numbered correctly from 0.
            // This prevent the Elasticsearch client to cast this array as an object in queries.
            $value = array_values(array_filter($value, 'strlen'));
        }

        $productCondition->setValue($value);

        return $this;
    }
}
