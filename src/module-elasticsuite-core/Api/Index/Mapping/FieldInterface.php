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

namespace Smile\ElasticsuiteCore\Api\Index\Mapping;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Representation of a Elasticsearch field (abstraction of mapping properties).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface FieldInterface
{
    /**
     * Field types declaration.
     */
    const FIELD_TYPE_TEXT    = 'text';
    const FIELD_TYPE_KEYWORD = 'keyword';
    const FIELD_TYPE_DOUBLE  = 'double';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_LONG    = 'long';
    const FIELD_TYPE_DATE    = 'date';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_NESTED  = 'nested';
    const FIELD_TYPE_OBJECT  = 'object';

    /**
     * Analyzers declarations.
     */
    const ANALYZER_STANDARD   = 'standard';
    const ANALYZER_WHITESPACE = 'whitespace';
    const ANALYZER_SHINGLE    = 'shingle';
    const ANALYZER_SORTABLE   = 'sortable';
    const ANALYZER_PHONETIC   = 'phonetic';
    const ANALYZER_UNTOUCHED  = 'untouched';

    /**
     * Field name.
     *
     * @return string
     */
    public function getName();

    /**
     * Field type (eg: string, integer, date).
     * See const above for available types.
     *
     * @return string
     */
    public function getType();

    /**
     * Is the field searchable.
     *
     * @return boolean
     */
    public function isSearchable();

    /**
     * Is the field filterable in navigation.
     *
     * @return boolean
     */
    public function isFilterable();

    /**
     * Is the attribute used in sorting.
     */
    public function isUsedForSortBy();

    /**
     * Is the field used by the spellchecker.
     *
     * @return boolean
     */
    public function isUsedInSpellcheck();

    /**
     * Weight of the fields in search.
     *
     * @return integer
     */
    public function getSearchWeight();

    /**
     * Return true if the field has a nested path.
     *
     * @return boolean
     */
    public function isNested();

    /**
     * Returns nested path for the field (Example : "category" for "category.position").
     *
     * @return string
     */
    public function getNestedPath();

    /**
     * Get nested field name (Example: "position" for "category.position").
     * Returns null for non nested fields.
     *
     * @return string|null
     */
    public function getNestedFieldName();

    /**
     * Return ES mapping properties associated with the field.
     *
     * @return array
     */
    public function getMappingPropertyConfig();

    /**
     * Return ES property name eventually using a specified analyzer.
     *
     * @param string $analyzer Analyzer for multi_type / string fields.
     *
     * @return string|null
     */
    public function getMappingProperty($analyzer = self::ANALYZER_UNTOUCHED);

    /**
     * Return the search analyzer used by default for fulltext searches.
     *
     * @return string
     */
    public function getDefaultSearchAnalyzer();

    /**
     * Merge field config and return a new instance with the updated config.
     *
     * @param array $config field configuration to merge with existing.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function mergeConfig(array $config = []);

    /**
     * Retrieve the directive to apply for "missing" when the field is used for sort by.
     *
     * @param string $direction The direction used to sort
     *
     * @return mixed
     */
    public function getSortMissing($direction = SortOrderInterface::SORT_ASC);

    /**
     * @return array
     */
    public function getConfig();
}
