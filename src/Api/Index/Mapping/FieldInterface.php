<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Api\Index\Mapping;

/**
 * Representation of a ElasticSearch field (abstraction of mapping properties).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface FieldInterface
{
    const FIELD_TYPE_STRING  = 'string';
    const FIELD_TYPE_DOUBLE  = 'double';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_DATE    = 'date';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_NESTED  = 'nested';

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
     * @return boolean
     */
    public function isSearchable();

    /**
     * @return boolean
     */
    public function isFilterable();

    /**
     * @return boolean
     */
    public function isUsedInSpellcheck();

    /**
     * @return boolean
     */
    public function isUsedInAutocomplete();

    /**
     * @return boolean
     */
    public function getSearchWeight();

    /**
     * @return boolean
     */
    public function isFilterableInSearch();

    /**
     * @return boolean
     */
    public function isNested();

    /**
     * @return string
     */
    public function getNestedPath();
}
