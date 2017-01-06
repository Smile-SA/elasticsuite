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

namespace Smile\ElasticsuiteCore\Index\Mapping;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Default implementation for ES mapping field (Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface).
 *
 * @SuppressWarnings(ExcessiveClassComplexity)
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Field implements FieldInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $nestedPath;

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
     * @var array
     */
    private $config = [
        'is_searchable'           => false,
        'is_filterable'           => true,
        'is_used_for_sort_by'     => false,
        'is_used_in_spellcheck'   => false,
        'is_used_in_autocomplete' => false,
        'search_weight'           => 1,
    ];

    /**
     * Constructor.
     *
     * @param string      $name        Field name.
     * @param string      $type        Field type.
     * @param null|string $nestedPath  Path for nested fields. null by default and for non-nested fields.
     * @param array       $fieldConfig Field configuration (see self::$config declaration for
     *                                 available values and default values).
     */
    public function __construct($name, $type = 'string', $nestedPath = null, $fieldConfig = [])
    {
        $this->name       = (string) $name;
        $this->type       = (string) $type;
        $this->config     = $fieldConfig + $this->config;
        $this->nestedPath = $nestedPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return (bool) $this->config['is_searchable'];
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return (bool) $this->config['is_filterable'];
    }

    /**
     * {@inheritDoc}
     */
    public function isUsedForSortBy()
    {
        return (bool) $this->config['is_used_for_sort_by'];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInSpellcheck()
    {
        return (bool) $this->config['is_used_in_spellcheck'];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInAutocomplete()
    {
        return (bool) $this->config['is_used_in_autocomplete'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchWeight()
    {
        return (int) $this->config['search_weight'];
    }

    /**
     * {@inheritdoc}
     */
    public function isNested()
    {
        return is_string($this->nestedPath) && !empty($this->nestedPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getNestedPath()
    {
        return $this->nestedPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getNestedFieldName()
    {
        $nestedFieldName = null;

        if ($this->isNested()) {
            $nestedPrefix = $this->getNestedPath() . '.';
            $nestedFieldName = str_replace($nestedPrefix, '', $this->getName());
        }

        return $nestedFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getMappingPropertyConfig()
    {
        $property = $this->getPropertyConfig();

        if ($this->getType() == self::FIELD_TYPE_STRING) {
            $analyzers = $this->getFieldAnalyzers();
            $property = $this->getPropertyConfig(current($analyzers));

            if (count($analyzers) > 1) {
                $property = $this->getMultiFieldMappingPropertyConfig($analyzers);
            }
        }

        return $property;
    }

    /**
     * {@inheritDoc}
     */
    public function getMappingProperty($analyzer = self::ANALYZER_UNTOUCHED)
    {
        $fieldName    = $this->getName();
        $propertyName = $fieldName;
        $property     = $this->getMappingPropertyConfig();

        if ($property['type'] == self::FIELD_TYPE_MULTI) {
            $isDefaultAnalyzer = $analyzer == self::ANALYZER_STANDARD;
            $subFieldName = $isDefaultAnalyzer ? $fieldName : $analyzer;
            $propertyName = null;

            if (isset($property['fields'][$subFieldName])) {
                $property     = $property['fields'][$subFieldName];
                $propertyName = $isDefaultAnalyzer ? $fieldName : sprintf("%s.%s", $fieldName, $subFieldName);
            }
        }

        if (!$this->checkAnalyzer($property, $analyzer)) {
            $propertyName = null;
        }

        return $propertyName;
    }

    /**
     * Check if an ES property as the right analyzer.
     *
     * @param array  $property         ES Property.
     * @param string $expectedAnalyzer Analyzer expected for the property.
     *
     * @return boolean
     */
    private function checkAnalyzer($property, $expectedAnalyzer)
    {
        $isAnalyzerCorrect = true;

        if ($property['type'] == self::FIELD_TYPE_STRING) {
            $isAnalyzed = $expectedAnalyzer !== self::ANALYZER_UNTOUCHED;

            if ($isAnalyzed && (!isset($property['analyzer']) || $property['analyzer'] != $expectedAnalyzer)) {
                $isAnalyzerCorrect = false;
            } elseif (!$isAnalyzed && (!isset($property['index']) || $property['index'] != 'not_analyzed')) {
                $isAnalyzerCorrect = false;
            }
        }

        return $isAnalyzerCorrect;
    }

    /**
     * Build a multi_field configuration from an analyzers list.
     * Standard analyzer is used as default subfield and should always be present.
     *
     * If the standard analyzer is not present, no default subfield is defined.
     *
     * @param array $analyzers List of analyzers used as subfields.
     *
     * @return array
     */
    private function getMultiFieldMappingPropertyConfig($analyzers)
    {
        // Setting the field type to "multi_field".
        $property = ['type' => self::FIELD_TYPE_MULTI];

        foreach ($analyzers as $analyzer) {
            // Using the analyzer name as subfield name by default.
            $subFieldName = $analyzer;

            if ($analyzer == self::ANALYZER_STANDARD && $this->isNested()) {
                // Using the field suffix as default subfield name for nested fields.
                $subFieldName = $this->getNestedFieldName();
            } elseif ($analyzer == self::ANALYZER_STANDARD) {
                // Using the field name as default subfield name for normal fields.
                $subFieldName = $this->getName();
            }

            $property['fields'][$subFieldName] = $this->getPropertyConfig($analyzer);
        }

        return $property;
    }

    /**
     * Retrieve analyzers used with the current field depending of the field configuration.
     *
     * @return array
     */
    private function getFieldAnalyzers()
    {
        $analyzers = [];

        if ($this->isSearchable() || $this->isUsedForSortBy()) {
            // Default search analyzer.
            $analyzers = [self::ANALYZER_STANDARD];

            if ($this->isSearchable() && $this->getSearchWeight() > 1) {
                $analyzers[] = self::ANALYZER_WHITESPACE;
                $analyzers[] = self::ANALYZER_SHINGLE;
            }
        }

        if ($this->isFilterable() || empty($analyzers)) {
            // For filterable fields or fields without analyzer : append the untouched analyzer.
            $analyzers[] = self::ANALYZER_UNTOUCHED;
        }

        if ($this->isUsedForSortBy()) {
            $analyzers[] = self::ANALYZER_SORTABLE;
        }

        return $analyzers;
    }

    /**
     * Build the property config from the field type and an optional
     * analyzer (used for string and detected through getAnalyzers).
     *
     * @param string|null $analyzer Used analyzer.
     *
     * @return array
     */
    private function getPropertyConfig($analyzer = self::ANALYZER_UNTOUCHED)
    {
        $fieldMapping = ['type' => $this->getType(), 'doc_values' => true, 'norms' => ['enabled' => false]];

        if ($this->getType() == self::FIELD_TYPE_STRING && $analyzer == self::ANALYZER_UNTOUCHED) {
            $fieldMapping['index'] = 'not_analyzed';
        } elseif ($this->getType() == self::FIELD_TYPE_STRING) {
            $fieldMapping['analyzer']   = $analyzer;
            $fieldMapping['doc_values'] = false;
            $fieldMapping['index_options'] = 'docs';
            if (in_array($analyzer, [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE])) {
                $fieldMapping['index_options'] = 'positions';
            }
            if ($analyzer !== self::ANALYZER_SORTABLE) {
                $fieldMapping['fielddata'] = ['format' => 'disabled'];
            }
        } elseif ($this->getType() == self::FIELD_TYPE_DATE) {
            $fieldMapping['format'] = implode('||', $this->dateFormats);
        }

        return $fieldMapping;
    }
}
