<?php

namespace Smile\ElasticSuiteCore\Api\Index\Mapping;

interface FieldInterface
{

    const FIELD_TYPE_STRING  = 'string';
    const FIELD_TYPE_DOUBLE  = 'double';
    const FIELD_TYPE_INTEGER = 'integer';
    const FIELD_TYPE_DATE    = 'date';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_NESTED  = 'nested';

    public function getName();
    public function getType();
    public function isSearchable();
    public function isFilterable();
    public function isUsedInSpellcheck();
    public function isUsedInAutocomplete();
    public function getSearchWeight();
    public function isFilterableInSearch();

    public function isNested();
    public function getNestedPath();

}