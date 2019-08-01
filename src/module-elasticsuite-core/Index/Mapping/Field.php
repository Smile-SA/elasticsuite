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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index\Mapping;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Default implementation for ES mapping field (Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface).
 *
 * @SuppressWarnings(ExcessiveClassComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Field implements FieldInterface
{
    /**
     * @var int
     */
    private const IGNORE_ABOVE_COUNT = 256;

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
        'search_weight'           => 1,
        'default_search_analyzer' => self::ANALYZER_STANDARD,
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
    public function __construct($name, $type = self::FIELD_TYPE_KEYWORD, $nestedPath = null, $fieldConfig = [])
    {
        $this->name       = (string) $name;
        $this->type       = (string) $type;
        $this->config     = $fieldConfig + $this->config;
        $this->nestedPath = $nestedPath;

        if ($nestedPath !== null && strpos($name, $nestedPath . '.') !== 0) {
            throw new \InvalidArgumentException('Invalid nested path or field name');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(): bool
    {
        return (bool) $this->config['is_searchable'];
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return (bool) $this->config['is_filterable'];
    }

    /**
     * {@inheritDoc}
     */
    public function isUsedForSortBy(): bool
    {
        return (bool) $this->config['is_used_for_sort_by'];
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInSpellcheck(): bool
    {
        return (bool) $this->config['is_used_in_spellcheck'] && (bool) $this->config['is_searchable'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchWeight(): int
    {
        return (int) $this->config['search_weight'];
    }

    /**
     * {@inheritdoc}
     */
    public function isNested(): bool
    {
        return is_string($this->nestedPath) && !empty($this->nestedPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getNestedPath(): ?string
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
    public function getMappingPropertyConfig(): array
    {
        $property = $this->getPropertyConfig();

        if ($this->getType() === self::FIELD_TYPE_TEXT) {
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
        $isDefaultAnalyzer = $analyzer === $this->getDefaultSearchAnalyzer();

        if (!$isDefaultAnalyzer && isset($property['fields'])) {
            $propertyName = null;

            if (isset($property['fields'][$analyzer])) {
                $property     = $property['fields'][$analyzer];
                $propertyName = $isDefaultAnalyzer ? $fieldName : sprintf('%s.%s', $fieldName, $analyzer);
            }
        }

        if (!$this->checkAnalyzer($property, $analyzer)) {
            $propertyName = null;
        }

        return $propertyName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSearchAnalyzer()
    {
        return $this->config['default_search_analyzer'];
    }

    /**
     * {@inheritDoc}
     */
    public function mergeConfig(array $config = [])
    {
        $config = array_merge($this->config, $config);

        return new static($this->name, $this->type, $this->nestedPath, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function getSortMissing($direction = SortOrderInterface::SORT_ASC)
    {
        // @codingStandardsIgnoreStart
        $missing = $direction === SortOrderInterface::SORT_ASC ? SortOrderInterface::MISSING_LAST : SortOrderInterface::MISSING_FIRST;
        // @codingStandardsIgnoreEnd

        if ($direction === SortOrderInterface::SORT_ASC && isset($this->config['sort_order_asc_missing'])) {
            $missing = $this->config['sort_order_asc_missing'];
        } elseif ($direction === SortOrderInterface::SORT_DESC && isset($this->config['sort_order_desc_missing'])) {
            $missing = $this->config['sort_order_desc_missing'];
        }

        return $missing;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): array
    {
        return $this->config ?? [];
    }

    /**
     * Check if an ES property as the right analyzer.
     *
     * @param array  $property         ES Property.
     * @param string $expectedAnalyzer Analyzer expected for the property.
     *
     * @return boolean
     */
    private function checkAnalyzer($property, $expectedAnalyzer): bool
    {
        $isAnalyzerCorrect = true;

        if ($property['type'] === self::FIELD_TYPE_TEXT || $property['type'] === self::FIELD_TYPE_KEYWORD) {
            $isAnalyzed = $expectedAnalyzer !== self::ANALYZER_UNTOUCHED;

            if ($isAnalyzed && (!isset($property['analyzer']) || $property['analyzer'] !== $expectedAnalyzer)) {
                $isAnalyzerCorrect = false;
            } elseif (!$isAnalyzed && $property['type'] !== self::FIELD_TYPE_KEYWORD) {
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return array
     */
    private function getMultiFieldMappingPropertyConfig($analyzers): array
    {
        // Setting the field type to "multi_field".
        $property = [];

        foreach ($analyzers as $analyzer) {
            if ($analyzer === $this->getDefaultSearchAnalyzer()) {
                $property = array_merge($property, $this->getPropertyConfig($analyzer));
            } else {
                $property['fields'][$analyzer] = $this->getPropertyConfig($analyzer);
            }
        }

        return $property;
    }

    /**
     * Retrieve analyzers used with the current field depending of the field configuration.
     *
     * @return array
     */
    private function getFieldAnalyzers(): array
    {
        $analyzers = [];

        if ($this->isSearchable() || $this->isUsedForSortBy()) {
            // Default search analyzer.
            $analyzers = [$this->getDefaultSearchAnalyzer()];
        }
        if ($this->isSearchable() && $this->getSearchWeight() > 1) {
            $analyzers[] = self::ANALYZER_WHITESPACE;
            $analyzers[] = self::ANALYZER_SHINGLE;
        }

        if (empty($analyzers) || $this->isFilterable()) {
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
    private function getPropertyConfig($analyzer = self::ANALYZER_UNTOUCHED): array
    {
        $fieldMapping = ['type' => $this->getType()];

        switch ($this->getType()) {
            case self::FIELD_TYPE_TEXT:
                if ($analyzer === self::ANALYZER_UNTOUCHED) {
                    $fieldMapping['type'] = self::FIELD_TYPE_KEYWORD;
                    $fieldMapping['ignore_above'] = self::IGNORE_ABOVE_COUNT;
                }
                if ($analyzer !== self::ANALYZER_UNTOUCHED) {
                    $fieldMapping['analyzer'] = $analyzer;
                    if ($analyzer === self::ANALYZER_SORTABLE) {
                        $fieldMapping['fielddata'] = true;
                    }
                }
                break;
            case self::FIELD_TYPE_DATE:
                $fieldMapping['format'] = implode('||', $this->dateFormats);
                break;
        }

        return $fieldMapping;
    }
}
