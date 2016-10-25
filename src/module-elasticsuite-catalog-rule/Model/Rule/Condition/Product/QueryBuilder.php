<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
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
     * @var NestedFilterInterface[]
     */
    private $nestedFilters;

    /**
     * Constructor.
     *
     * @param AttributeList           $attributeList Search rule product attributes list
     * @param QueryFactory            $queryFactory  Search query factory.
     * @param NestedFilterInterface[] $nestedFilters Filters applied to nested fields during query building.
     */
    public function __construct(AttributeList $attributeList, QueryFactory $queryFactory, $nestedFilters = [])
    {
        $this->queryFactory  = $queryFactory;
        $this->attributeList = $attributeList;
        $this->nestedFilters = $nestedFilters;
    }

    /**
     * Build the query for a condition.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getSearchQuery(ProductCondition $productCondition)
    {
        $query = null;

        $query = $this->getSpecialAttributesSearchQuery($productCondition);

        $this->prepareFieldValue($productCondition);

        if ($query === null && !empty($productCondition->getValue())) {
            $queryType   = QueryInterface::TYPE_TERMS;
            $queryParams = $this->getTermsQueryParams($productCondition);

            if ($productCondition->getInputType() === 'string') {
                $queryType = QueryInterface::TYPE_MATCH;
                $queryParams = $this->getMatchQueryParams($productCondition);
            } elseif (in_array($productCondition->getOperator(), ['>=', '>', '<=', '<'])) {
                $queryType = QueryInterface::TYPE_RANGE;
                $queryParams = $this->getRangeQueryParams($productCondition);
            }

            $query = $this->prepareQuery($queryType, $queryParams);

            if (substr($productCondition->getOperator(), 0, 1) === '!') {
                $query = $this->applyNegation($query);
            }

            $field = $this->getSearchField($productCondition);

            if ($field->isNested()) {
                $nestedPath = $field->getNestedPath();
                $nestedQueryParams = ['query' => $query, 'path' => $nestedPath];

                if (isset($this->nestedFilters[$nestedPath])) {
                    $nestedFilterClauses = [];
                    $nestedFilterClauses['must'][] = $this->nestedFilters[$nestedPath]->getFilter();
                    $nestedFilterClauses['must'][] = $nestedQueryParams['query'];

                    $nestedFilter = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $nestedFilterClauses);

                    $nestedQueryParams['query'] = $nestedFilter;
                }

                $query = $this->queryFactory->create(QueryInterface::TYPE_NESTED, $nestedQueryParams);
            }
        }

        return $query;
    }

    /**
     * Create a query for special attribute.
     *
     * @param ProductCondition $productCondition Product condition.
     *
     * @return NULL|\Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getSpecialAttributesSearchQuery(ProductCondition $productCondition)
    {
        $query = null;

        if ($productCondition->getAttribute() == 'has_image' && $productCondition->getValue()) {
            $query = $this->getHasImageQuery();
        }

        return $query;
    }

    /**
     * Has image query.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getHasImageQuery()
    {
        $query = $this->queryFactory->create(QueryInterface::TYPE_MISSING, ['field' => 'image']);

        return $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $query]);
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

        return ['field' => $fieldName, 'queryText' => $queryText, 'minimumShouldMatch' => $minimumShouldMatch];
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
        $operator  = $productCondition->getOperator();
        $value     = $productCondition->getValue();

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

        return ['bounds' => [$operator => (float) $value], 'field' => $fieldName];
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
        $fieldName = $this->getSearchFieldName($productCondition);
        $values    = $productCondition->getValue();

        if (!is_array($values) && in_array($productCondition->getOperator(), ['()', '!()'])) {
            $values = explode(',', $values);
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
     * @return \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\FieldInterface
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

        if ($productCondition->getInputType() === "string") {
            $analyzer = FieldInterface::ANALYZER_STANDARD;
        }

        return $field->getMappingProperty($analyzer);
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
            $value = array_filter($value);
        }

        $productCondition->setValue($value);

        return $this;
    }
}
