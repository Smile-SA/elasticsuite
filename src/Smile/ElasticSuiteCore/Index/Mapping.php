<?php

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;

class Mapping implements MappingInterface
{
    private $dateFormats = [\Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT, \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT];

    private $defaultFields = [
        self::DEFAULT_SEARCH_FIELD       => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE]],
        self::DEFAULT_SPELLING_FIELD     => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE]],
        self::DEFAULT_AUTOCOMPLETE_FIELD => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE, self::ANALYZER_EDGE_NGRAM]],
    ];

    private $fields;

    public function __construct(array $staticFields = [], $dynamicFieldProviders = [])
    {
        $this->fields = $staticFields + $this->getDynamicFields($dynamicFieldProviders);
    }

    private function getDynamicFields($dynamicFieldProviders)
    {
        $fields = [];
        foreach ($dynamicFieldProviders as $dynamicFieldProvider) {
            $fields += $dynamicFieldProvider->getFields();
        }
        return $fields;
    }

    public function asArray()
    {
        $mappingArray = ['_all' => ['enabled' => false], 'properties' => $this->getProperties()];
        return $mappingArray;
    }

    public function getProperties()
    {
        $properties = [];

        foreach ($this->defaultFields as $currentFieldName => $fieldConfig) {
            $analyzers = $fieldConfig['analyzers'];
            $properties[$currentFieldName] = $this->getPropertyMapping($currentFieldName, 'string', $analyzers);
        }

        foreach ($this->getFields() as $currentField) {
            if ($currentField->isNested()) {
                $nestedRoot = $currentField->getNestedPath();
                $subFieldName = str_replace($nestedRoot . '.', '', $currentField->getName());
                $properties[$nestedRoot]['type'] = FieldInterface::FIELD_TYPE_NESTED;
                $properties[$nestedRoot]['properties'][$subFieldName] = $this->getPropertyFromField($currentField);
            } else {
                $properties[$currentField->getName()] = $this->getPropertyFromField($currentField);
            }
        }

        return $properties;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\MappingInterface::getFields()
     */
    public function getFields()
    {
        return $this->fields;
    }

    private function getPropertyMapping($propertyName, $type, $analyzers = [self::ANALYZER_STANDARD], $copyTo = [])
    {
        $fieldMapping = ['type' => $type];

        if ($type == "string") {
            if (count($analyzers) > 1) {
                $fieldMapping = ['type' => 'multi_field'];

                foreach ($analyzers as $currentAnalyzer) {

                    $currentFieldName = $currentAnalyzer == self::ANALYZER_STANDARD ? $propertyName : $currentAnalyzer;
                    $subField = ['type'  => 'string', 'store' => false];

                    if ($currentAnalyzer == self::ANALYZER_UNTOUCHED) {
                        $subField['index']     = 'not_analyzed';
                        $subField['fieldData'] = ['format' => 'doc_values'];
                    } else {
                        $subField['analyzer']  = $currentAnalyzer;
                    }

                    if ($currentFieldName == $propertyName && !empty($copyTo)) {
                        $subField['copy_to'] = $copyTo;
                    }

                    $fieldMapping['fields'][$currentFieldName] = $subField;
                }
            } else {
                $analyzer = current($analyzers);
                if ($analyzer == self::ANALYZER_UNTOUCHED) {
                    $fieldMapping['index'] = 'not_analyzed';
                } else {
                    $fieldMapping['analyzer'] = $analyzer;
                }
            }
        } else if ($type == "date") {
            $fieldMapping['format'] = implode('||', $this->dateFormats);
        }

        return $fieldMapping;
    }

    private function getPropertyFromField(FieldInterface $field)
    {
        $analyzers = [];
        $copyTo    = [];

        if ($field->getType() == "string") {
            $analyzers = [self::ANALYZER_UNTOUCHED];

            if ($field->isSearchable()) {
                $analyzers = array_merge($analyzers, [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE]);
                $copyTo[]  = self::DEFAULT_SEARCH_FIELD;

                if ($field->isUsedInSpellcheck()) {
                    $copyTo[] = self::DEFAULT_SPELLING_FIELD;
                }

                if ($field->isUsedInAutocomplete()) {
                    $analyzers[] = self::ANALYZER_EDGE_NGRAM;
                    $copyTo[] = self::DEFAULT_SPELLING_FIELD;
                }
            }
        }

        return $this->getPropertyMapping($field->getName(), $field->getType(), $analyzers, $copyTo);
    }
}