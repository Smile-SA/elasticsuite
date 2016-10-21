<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index\Mapping;

/**
 * Representation of a Elasticsearch field (abstraction of mapping properties).
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface FieldInterface
{
    /**
     * Field types declaration.
     *
     */

    const FIELD_TYPE_STRING  = 'string';
    const FIELD_TYPE_DOUBLE  = 'double';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_DATE    = 'date';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_NESTED  = 'nested';
    const FIELD_TYPE_MULTI   = 'multi_field';
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
     * Is the field used for autocomplete.
     *
     * @return boolean
     */
    public function isUsedInAutocomplete();

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
}
