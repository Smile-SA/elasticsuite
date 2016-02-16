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

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Default implementation for ES mappings (Smile\ElasticSuiteCore\Api\Index\MappingInterface).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Mapping implements MappingInterface
{
    /**
     * List of fields for the current mapping.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    private $fields;

    /**
     * List of default fields and associated analyzers.
     *
     * @var array
     */
    private $defaultFields = [
        self::DEFAULT_SEARCH_FIELD => [
            'analyzers' => [
                self::ANALYZER_STANDARD,
                self::ANALYZER_WHITESPACE,
                self::ANALYZER_SHINGLE,
            ],
        ],
        self::DEFAULT_SPELLING_FIELD => [
            'analyzers' => [
                self::ANALYZER_STANDARD,
                self::ANALYZER_WHITESPACE,
                self::ANALYZER_SHINGLE,
            ],
        ],
        self::DEFAULT_AUTOCOMPLETE_FIELD => [
            'analyzers' => [
                self::ANALYZER_STANDARD,
                self::ANALYZER_WHITESPACE,
                self::ANALYZER_SHINGLE,
                self::ANALYZER_EDGE_NGRAM,
            ],
        ],
    ];

    /**
     * Date formats used by the indices.
     *
     * @var array
     */
    private $dateFormats = [
        \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
        \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
    ];

    /**
     * Instanciate a new mapping.
     *
     * @param FieldInterface[]                $staticFields          List of static fields.
     * @param DynamicFieldProviderInterface[] $dynamicFieldProviders Dynamic fields providers.
     */
    public function __construct(array $staticFields = [], array $dynamicFieldProviders = [])
    {
        $this->fields = $staticFields + $this->getDynamicFields($dynamicFieldProviders);
    }

    /**
     * {@inheritDoc}
     */
    public function asArray()
    {
        return ['_all' => ['enabled' => false], 'properties' => $this->getProperties()];
    }

    /**
     * {@inheritDoc}
     */
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
     * {@inheritDoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Retrieve the fields provided by differents providers.
     *
     * @param DynamicFieldProviderInterface[] $dynamicFieldProviders List of dynamic fields providers
     *
     * @return array
     */
    private function getDynamicFields(array $dynamicFieldProviders)
    {
        $fields = [];
        foreach ($dynamicFieldProviders as $dynamicFieldProvider) {
            $fields += $dynamicFieldProvider->getFields();
        }

        return $fields;
    }

    /**
     * Build a mapping property from it's name, type and analyzers (for string values).
     *
     * @param string $propertyName Name of the property field.
     * @param string $type         ES field type.
     * @param array  $analyzers    For string properties, list of analyzers.
     * @param array  $copyTo       Copy the properties to another or several ones.
     *
     * @return array
     */
    private function getPropertyMapping(
        $propertyName,
        $type,
        array $analyzers = [self::ANALYZER_STANDARD],
        array  $copyTo = []
    ) {
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
        } elseif ($type == "date") {
            $fieldMapping['format'] = implode('||', $this->dateFormats);
        }

        return $fieldMapping;
    }

    /**
     * Convert a FieldInterface object to a ES mapping property.
     *
     * @param \Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface $field Transformed field.
     *
     * @return array
     */
    private function getPropertyFromField(FieldInterface $field)
    {
        $analyzers = [];
        $copyTo    = [];

        if ($field->getType() == "string") {
            $analyzers = [self::ANALYZER_UNTOUCHED];

            if ($field->isSearchable()) {
                $searchAnalyzers = [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE];
                $analyzers = array_merge($analyzers, $searchAnalyzers);
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
