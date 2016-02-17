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
 * @todo : sortable fields ???
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
    private $isSearchable;

    /**
     * @var boolean
     */
    private $isFilterable;

    /**
     * @var boolean
     */
    private $isFilterableInSearch;

    /**
     * @var boolean
     */
    private $isUsedInSpellcheck;

    /**
     * @var boolean
     */
    private $isUsedInAutocomplete;

    /**
     * @var boolean
     */
    private $searchWeight;

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
     * Instanciate a new field.
     *
     * @param string  $name                 Field name.
     * @param string  $type                 Field type.
     * @param boolean $isSearchable         Is the field searchable.
     * @param boolean $isFilterable         Is the field filterabe in navigation.
     * @param boolean $isFilterableInSearch Is the field filterabe in search.
     * @param boolean $isUsedInSpellcheck   Is the field used by the spellchecker.
     * @param boolean $isUsedInAutocomplete Is the field used in autocomplete.
     * @param integer $searchWeight         Field weight in search operation.
     * @param string  $nestedPath           If the field is nested, the nested path have to be provided here.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(
        $name,
        $type = 'string',
        $isSearchable = false,
        $isFilterable = false,
        $isFilterableInSearch = false,
        $isUsedInSpellcheck = false,
        $isUsedInAutocomplete = false,
        $searchWeight = 1,
        $nestedPath = false
    ) {
        $this->name                 = (string) $name;
        $this->type                 = (string) $type;
        $this->isSearchable         = (bool) $isSearchable;
        $this->isFilterable         = (bool) $isFilterable;
        $this->isFilterableInSearch = (bool) $isFilterableInSearch;
        $this->isUsedInSpellcheck   = (bool) $isUsedInSpellcheck;
        $this->isUsedInAutocomplete = (bool) $isUsedInAutocomplete;
        $this->searchWeight         = (int) $searchWeight;
        $this->nestedPath           = $nestedPath;
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
        return $this->isSearchable;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return $this->isFilterable;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterableInSearch()
    {
        return $this->isFilterableInSearch;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInSpellcheck()
    {
        return $this->isUsedInSpellcheck;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInAutocomplete()
    {
        return $this->isUsedInAutocomplete;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchWeight()
    {
        return $this->getSearchWeight();
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
        $nestedFieldName = false;

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

        if ($this->isFilterable() || $this->isFilterableInSearch()  || empty($analyzers)) {
            // For filterable fields or fields without analyzer : append the untouched analyzer.
            $analyzers[] = self::ANALYZER_UNTOUCHED;
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
