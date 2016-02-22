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

namespace Smile\ElasticSuiteCore\Index\Mapping;

use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Default implementation for ES mapping field (Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Field implements FieldInterface
{
    /**
     * @var boolean
     */
    private $name;

    /**
     * @var boolean
     */
    private $type;

    /**
     * @var boolean
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
        'is_facet'                => [],
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
     * {@inheritdoc}
     */
    public function isFacet($requestName)
    {
        return (bool) in_array($requestName, $this->config['is_facet']);
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

        if ($this->isSearchable()) {
            // Default search analyzer.
            $analyzers = [self::ANALYZER_STANDARD, self::ANALYZER_WHITESPACE, self::ANALYZER_SHINGLE];

            if ($this->isUsedInAutocomplete()) {
                // Append edge_ngram analyzer when the field is used in autocomplete.
                $analyzers[] = self::ANALYZER_EDGE_NGRAM;
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
    private function getPropertyConfig($analyzer = null)
    {
        $fieldMapping = ['type' => $this->getType(), 'fielddata' => ['format' => 'doc_values']];

        if ($this->getType() == self::FIELD_TYPE_STRING && $analyzer == self::ANALYZER_UNTOUCHED) {
            $fieldMapping['index'] = 'not_analyzed';
        } elseif ($this->getType() == self::FIELD_TYPE_STRING) {
            $fieldMapping['fielddata'] = ['format' => 'lazy'];
            $fieldMapping['analyzer']  = $analyzer != null ? $analyzer : self::ANALYZER_UNTOUCHED;
        } elseif ($this->getType() == self::FIELD_TYPE_DATE) {
            $fieldMapping['format'] = implode('||', $this->dateFormats);
        }

        return $fieldMapping;
    }
}
