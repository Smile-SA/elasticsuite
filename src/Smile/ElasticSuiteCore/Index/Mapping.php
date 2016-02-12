<?php

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\MappingInterface;

class Mapping implements MappingInterface
{
    private $defaultFields = [
        self::DEFAULT_SEARCH_FIELD       => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE]],
        self::DEFAULT_SPELLING_FIELD     => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE]],
        self::DEFAULT_AUTOCOMPLETE_FIELD => ['analyzers' => [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE, self::ANALYZER_EDGE_NGRAM]],
    ];

    private $fieldDescriptions;

    public function __construct($fieldDescriptions)
    {
        $this->fieldDescriptions = $fieldDescriptions;
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
            $properties[$currentFieldName] = $this->getStringFieldMapping($currentFieldName, $fieldConfig['analyzers']);
        }

        foreach ($this->fieldDescriptions as $currentFieldName => $fieldConfig) {
            $properties[$currentFieldName] = $this->getPropertyFromFieldDescription($currentFieldName, $fieldConfig);
        }

        return $properties;
    }

    private function getStringFieldMapping($fieldName, $analyzers)
    {
        $fieldMapping = ['type' => 'multi_field'];

        foreach ($analyzers as $currentAnalyzer) {

            $currentFieldName = $currentAnalyzer == self::ANALYZER_STANDARD ? $fieldName : $currentAnalyzer;
            $subField = ['type'  => 'string', 'store' => false];

            if ($currentAnalyzer == self::ANALYZER_UNTOUCHED) {
                $subField['index']     = 'not_analyzed';
                $subField['fieldData'] = ['format' => 'doc_values'];
            } else {
                $subField['analyzer']  = $currentAnalyzer;
                $subField['fieldData'] = ['format' => 'disabled'];
            }

            $fieldMapping['fields'][$currentFieldName] = $subField;
        }

        if (count($fieldMapping['fields']) == 1) {
            $fieldMapping = current($fieldMapping['fields']);
        }

        return $fieldMapping;
    }

    private function getPropertyFromFieldDescription($fieldName, $fieldDescription)
    {
        $property = ['type' => $fieldDescription['type']];

        if ($fieldDescription['type'] == 'string') {
            $analyzers = [self::ANALYZER_STANDARD];
            $copyTo    = [];

            if ($fieldDescription['is_searchable']) {
                $analyzers += [self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE];
                $copyTo[]   = self::DEFAULT_SEARCH_FIELD;

                if ($fieldDescription['used_in_spellcheck']) {
                    $copyTo[] = self::DEFAULT_SPELLING_FIELD;
                }

                if ($fieldDescription['used_in_autocomplete']) {
                    $analyzers[] = self::ANALYZER_EDGE_NGRAM;
                    $copyTo[] = self::DEFAULT_AUTOCOMPLETE_FIELD;
                }
            }

            if ($fieldDescription['is_filterable'] || $fieldDescription['is_filterable']) {
                $analyzers[] = self::ANALYZER_UNTOUCHED;
            }

            $property = $this->getStringFieldMapping($fieldName, $analyzers);

            if ($property['type'] == 'multi_field') {
                $property['fields'][$fieldName]['copy_to'] = $copyTo;
            } else {
                $property['copy_to'] = $copyTo;
            }

        } else {
            $property['fieldData'] = ['format' => 'doc_values', 'store' => false];
        }

        return $property;
    }
}