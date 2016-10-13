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

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Default implementation for ES mappings (Smile\ElasticsuiteCore\Api\Index\MappingInterface).
 *
 * @category Smile_Elasticsuite
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
    ];

    /**
     * List of target field for copy to by field configuration.
     *
     * @var array
     */
    private $copyFieldMap = [
        'isSearchable'         => self::DEFAULT_SEARCH_FIELD,
        'isUsedInSpellcheck'   => self::DEFAULT_SPELLING_FIELD,
        'isUsedInAutocomplete' => self::DEFAULT_AUTOCOMPLETE_FIELD,
    ];

    /**
     * Instanciate a new mapping.
     *
     * @param string                          $idFieldName           Field use as unique id for the documents.
     * @param FieldInterface[]                $staticFields          List of static fields.
     * @param DynamicFieldProviderInterface[] $dynamicFieldProviders Dynamic fields providers.
     */
    public function __construct($idFieldName, array $staticFields = [], array $dynamicFieldProviders = [])
    {
        $this->fields      = $staticFields + $this->getDynamicFields($dynamicFieldProviders);
        $this->idFieldName = $idFieldName;
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

        foreach ($this->defaultMappingFields as $fieldName => $analyzers) {
            $properties = $this->addProperty($properties, $fieldName, FieldInterface::FIELD_TYPE_STRING, $analyzers);
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
        $property = ['type' => FieldInterface::FIELD_TYPE_MULTI];

        foreach ($analyzers as $analyzer) {
            $subFieldName = $analyzer == FieldInterface::ANALYZER_STANDARD ? $propertyName : $analyzer;
            $property['fields'][$subFieldName] = ['type' => $propertyType, 'analyzer' => $analyzer];
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

        if ($field->isNested()) {
            /*
             * Nested field management :
             *
             * For nested field we need to
             *   - change the insertion root to the parent field.
             *   - create the parent field with type nested if not yet exists.
             *   - using the suffix name of the field instead of the name including nested path.
             *
             * Ex: "price.is_discount" field has to be inserted with name "is_discount" into the "price" field.
             *
             */
            $nestedPath = $field->getNestedPath();

            if (!isset($properties[$nestedPath])) {
                $properties[$nestedPath] = ['type' => FieldInterface::FIELD_TYPE_NESTED, 'properties' => []];
            }

            $fieldRoot = &$properties[$nestedPath]['properties'];
            $fieldName = $field->getNestedFieldName();
        } elseif (strstr($fieldName, '.')) {
            $fieldPathArray = explode('.', $fieldName);
            if (!isset($properties[current($fieldPathArray)])) {
                $properties[current($fieldPathArray)] = [
                    'type' => FieldInterface::FIELD_TYPE_OBJECT,
                    'properties' => [],
                ];
            }
            $fieldRoot = &$properties[current($fieldPathArray)]['properties'];
            $fieldName = end($fieldPathArray);
        }

        /*
         * Retrieving location where the property has to be copied to.
         * Ex : searchable fields are copied to default "search" field.
         */
        $copyToProperties = $this->getFieldCopyToProperties($field);

        if (!empty($copyToProperties)) {
            // For normal fields, copy_to is append at the property root.
            $copyToRoot = &$property;
            if ($property['type'] == FieldInterface::FIELD_TYPE_MULTI) {
                /*
                 * For field with type "multi_field", the copy_to has to be added in the
                 * default subfield.
                 * This is changing the root.
                 */
                $copyToRoot = &$property['fields'][$fieldName];
            }

            $copyToRoot['copy_to'] = $copyToProperties;
        }

        $fieldRoot[$fieldName] = $property;

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
