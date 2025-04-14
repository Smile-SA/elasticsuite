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

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;

/**
 * Default implementation for ES mappings (Smile\ElasticsuiteCore\Api\Index\MappingInterface).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Mapping implements MappingInterface
{
    /**
     * @var string
     */
    private $idFieldName;

    /**
     * List of fields for the current mapping.
     *
     * @var \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    private $fields;

    /**
     * @var boolean
     */
    private bool $hasKnnFields = false;

    /**
     * List of default fields and associated analyzers.
     *
     * @var array
     */
    private $defaultMappingFields = [
        self::DEFAULT_SEARCH_FIELD       => [
            FieldInterface::ANALYZER_STANDARD,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
        ],
        self::DEFAULT_SPELLING_FIELD     => [
            FieldInterface::ANALYZER_STANDARD,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
            FieldInterface::ANALYZER_PHONETIC,
        ],
        self::DEFAULT_AUTOCOMPLETE_FIELD => [
            FieldInterface::ANALYZER_STANDARD,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
        ],
        self::DEFAULT_REFERENCE_FIELD => [
            FieldInterface::ANALYZER_REFERENCE,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
        ],
        self::DEFAULT_EDGE_NGRAM_FIELD => [
            FieldInterface::ANALYZER_EDGE_NGRAM,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
        ],
    ];

    /**
     * List of target field for copy to by field configuration.
     *
     * @var array
     */
    private $copyFieldMap = [
        'isSearchable'         => self::DEFAULT_SEARCH_FIELD,
        'isUsedInSpellcheck'   => self::DEFAULT_SPELLING_FIELD,
        'isSearchableReference' => self::DEFAULT_REFERENCE_FIELD,
        'isSearchableEdgeNgram' => self::DEFAULT_EDGE_NGRAM_FIELD,
    ];

    /**
     * Instantiate a new mapping.
     *
     * @param string           $idFieldName Field use as unique id for the documents.
     * @param FieldInterface[] $fields      List of mapping fields.
     */
    public function __construct($idFieldName, array $fields = [])
    {
        $this->fields      = $this->prepareFields($fields);
        $this->idFieldName = $idFieldName;

        if (!isset($this->fields[$this->idFieldName])) {
            throw new \InvalidArgumentException("Invalid id field $this->idFieldName : field is not declared.");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function asArray()
    {
        return ['properties' => $this->getProperties()];
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties()
    {
        $properties = [];

        foreach ($this->defaultMappingFields as $fieldName => $analyzers) {
            $properties = $this->addProperty($properties, $fieldName, FieldInterface::FIELD_TYPE_TEXT, $analyzers);
        }

        foreach ($this->getFields() as $currentField) {
            $properties = $this->addField($properties, $currentField);
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
     * {@inheritDoc}
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \LogicException("Field {$name} does not exists in mapping");
        }

        return $this->fields[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getIdField()
    {
        return $this->getField($this->idFieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getWeightedSearchProperties(
        ?string $analyzer = null,
        ?string $defaultField = null,
        int $boost = 1,
        ?FieldFilterInterface $fieldFilter = null
    ) {
        $weightedFields = [];
        $fields         = $this->getFields();

        if ($defaultField) {
            $defaultSearchProperty = $this->getDefaultSearchProperty($defaultField, $analyzer);
            $weightedFields[$defaultSearchProperty] = $boost;
        }

        if ($fieldFilter !== null) {
            $fields = array_filter($fields, [$fieldFilter, 'filterField']);
        }

        foreach ($fields as $field) {
            $currentAnalyzer = $analyzer;
            $canAddField     = $defaultField === null || $field->getSearchWeight() !== 1;

            if ($analyzer === null) {
                $currentAnalyzer = $field->getDefaultSearchAnalyzer();
                $canAddField     = $canAddField || ($currentAnalyzer !== FieldInterface::ANALYZER_STANDARD);
            }

            $property = $field->getMappingProperty($currentAnalyzer);

            if ($property && $canAddField) {
                $weightedFields[$property] = $boost * $field->getSearchWeight();
            }
        }

        return $weightedFields;
    }

    /**
     * {@inheritDoc}
     */
    public function hasKnnFields(): bool
    {
        return $this->hasKnnFields;
    }

    /**
     * Return the search property for a field present in defaultMappingFields.
     *
     * @throws \InvalidArgumentException If the field / analyzer does not exists.
     *
     * @param string $field    Field.
     * @param string $analyzer Required analyzer.
     *
     * @return string
     */
    private function getDefaultSearchProperty($field = self::DEFAULT_SEARCH_FIELD, $analyzer = null)
    {
        if (!isset($this->defaultMappingFields[$field])) {
            throw new \InvalidArgumentException("Unable to find field {$field}.");
        }

        $property = $field;

        if ($analyzer !== null) {
            if (!in_array($analyzer, $this->defaultMappingFields[$field])) {
                throw new \InvalidArgumentException("Unable to find analyzer {$analyzer} for field {$field}.");
            }

            $property = sprintf("%s.%s", $field, $analyzer);
        }

        return $property;
    }

    /**
     * Prepare the array of fields to be added to the mapping. Mostly rekey the array.
     *
     * @param array $fields Fields to be prepared.
     *
     * @return FieldInterface[]
     */
    private function prepareFields(array $fields)
    {
        $preparedFields = [];

        foreach ($fields as $field) {
            $preparedFields[$field->getName()] = $field;
            if ($field->getType() === FieldInterface::FIELD_TYPE_KNN_VECTOR) {
                $this->hasKnnFields = true;
            }
        }

        return $preparedFields;
    }

    /**
     * Append a new properties into a properties list and returned the updated map.
     *
     * @param array  $properties   Initial properties list.
     * @param string $propertyName New property name.
     * @param string $propertyType New property type.
     * @param array  $analyzers    Property analyzers.
     *
     * @return array
     */
    private function addProperty(array $properties, $propertyName, $propertyType, $analyzers = [])
    {
        $property = ['type' => $propertyType, 'analyzer' => FieldInterface::ANALYZER_STANDARD];

        foreach ($analyzers as $analyzer) {
            if ($analyzer !== FieldInterface::ANALYZER_STANDARD) {
                $property['fields'][$analyzer] = ['type' => $propertyType, 'analyzer' => $analyzer];
            }
        }

        $properties[$propertyName] = $property;

        return $properties;
    }

    /**
     * Append a field to a mapping properties list.
     * The field is append and the new properties list is returned.
     *
     * @param array          $properties Initial properties map.
     * @param FieldInterface $field      Field to be added.
     *
     * @return array
     */
    private function addField(array $properties, FieldInterface $field)
    {
        $fieldName = $field->getName();
        $fieldRoot = &$properties;

        // Read property config from the field.
        $property = $field->getMappingPropertyConfig();

        $fieldPathArray   = explode('.', $fieldName);
        $currentPathArray = [];
        $fieldPathSize    = count($fieldPathArray);

        for ($i = 0; $i < $fieldPathSize - 1; $i++) {
            $currentPathArray[] = $fieldPathArray[$i];
            $currentPath        = implode('.', $currentPathArray);

            if ($field->isNested() && $field->getNestedPath() == $currentPath && !isset($fieldRoot[$fieldPathArray[$i]])) {
                $fieldRoot[$fieldPathArray[$i]] = ['type' => FieldInterface::FIELD_TYPE_NESTED, 'properties' => []];
            } elseif (!isset($fieldRoot[$fieldPathArray[$i]])) {
                $fieldRoot[$fieldPathArray[$i]] = ['type' => FieldInterface::FIELD_TYPE_OBJECT, 'properties' => []];
            }

            $fieldRoot = &$fieldRoot[$fieldPathArray[$i]]['properties'];
        }

        /*
         * Retrieving location where the property has to be copied to.
         * Ex : searchable fields are copied to default "search" field.
         */
        $copyToProperties = $this->getFieldCopyToProperties($field);

        if (!empty($copyToProperties)) {
            // For normal fields, copy_to is append at the property root.
            $copyToRoot = &$property;
            $copyToRoot['copy_to'] = $copyToProperties;
        }

        $fieldRoot[end($fieldPathArray)] = $property;

        return $properties;
    }

    /**
     * Get the list of default fields where the current field must be copied.
     * Example : searchable fields are copied into the default "search" field.
     *
     * @param FieldInterface $field Field to be checked.
     *
     * @return array
     */
    private function getFieldCopyToProperties(FieldInterface $field)
    {
        $copyTo = [];

        foreach ($this->copyFieldMap as $method => $targetField) {
            if ($field->$method()) {
                $copyTo[] = $targetField;
            }
        }

        return $copyTo;
    }
}
