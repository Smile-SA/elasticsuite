<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Helper;

use Smile\ElasticsuiteCore\Helper\Mapping;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Abstract ElasticSuite catalog attributes helper.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractAttribute extends Mapping
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var array
     */
    private $storeAttributes = [];

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $attributesCode = [];

    /**
     * @var array
     */
    private $attributeOptionTextCache = [];

    /**
     * @var array
     */
    private $attributeFieldTypeCache = [];

    /**
     * @var array
     */
    private $attributeUsesSourceCache = [];

    /**
     * @var array
     */
    private $attributeBackendCache = [];

    /**
     * @var array
     */
    private $attributeFrontendCache = [];

    /**
     * @var array
     */
    private $attributeMappers = [];

    /**
     * @var array
     */
    private $attributeCleaners = [];

    /**
     * @param Context          $context           Helper context.
     * @param AttributeFactory $attributeFactory  Factory used to create attributes.
     * @param mixed            $collectionFactory Attribute collection factory.
     */
    public function __construct(Context $context, AttributeFactory $attributeFactory, $collectionFactory)
    {
        parent::__construct($context);
        $this->attributeFactory = $attributeFactory;
        $this->attributeCollectionFactory = $collectionFactory;
    }

    /**
     * Retrieve a new product attribute collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }

    /**
     * Parse attribute to get mapping field creation parameters.
     *
     * @param AttributeInterface $attribute Product attribute.
     *
     * @return array
     */
    public function getMappingFieldOptions(AttributeInterface $attribute)
    {
        $options = [
            'is_searchable'       => $attribute->getIsSearchable(),
            'is_filterable'       => $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch(),
            'search_weight'       => $attribute->getSearchWeight(),
            'is_used_for_sort_by' => $attribute->getUsedForSortBy(),
            'filter_logical_operator'   => $attribute->getFacetBooleanLogic(),
        ];

        if ($attribute->getIsUsedInSpellcheck()) {
            $options['is_used_in_spellcheck'] = true;
        }

        if ($attribute->getIsDisplayedInAutocomplete()) {
            $options['is_filterable'] = true;
        }

        if ($attribute->getUsedForSortBy()) {
            $options['sort_order_asc_missing']  = $attribute->getSortOrderAscMissing();
            $options['sort_order_desc_missing'] = $attribute->getSortOrderDescMissing();
        }

        if ($attribute->getIsSpannable()) {
            $options['is_spannable'] = $attribute->getIsSpannable();
        }

        if ($attribute->getNormsDisabled()) {
            $options['norms_disabled'] = $attribute->getNormsDisabled();
        }

        if ($attribute->getDefaultAnalyzer()) {
            $options['default_search_analyzer'] = $attribute->getDefaultAnalyzer();
        }

        if ($attribute->getScoringAlgorithm()) {
            $options['similarity'] = $attribute->getScoringAlgorithm();
        }

        return $options;
    }

    /**
     * Get mapping field type for an attribute.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param int $attributeId Product attribute id.
     *
     * @return string
     */
    public function getFieldType($attributeId)
    {
        // Backward compatibility.
        if (!is_numeric($attributeId)) {
            $attributeId = $this->getAttributeId($attributeId);
        }

        if (!isset($this->attributeFieldTypeCache[$attributeId])) {
            $attribute = $this->getAttributeById($attributeId);
            $type = FieldInterface::FIELD_TYPE_TEXT;

            if ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean') {
                $type = FieldInterface::FIELD_TYPE_BOOLEAN;
            } elseif ($this->getBackendType($attributeId) == 'int') {
                $type = FieldInterface::FIELD_TYPE_INTEGER;
            } elseif ($attribute->getFrontendClass() == 'validate-digits') {
                $type = FieldInterface::FIELD_TYPE_LONG;
            } elseif ($this->getBackendType($attributeId) == 'decimal' || $attribute->getFrontendClass() == 'validate-number') {
                $type = FieldInterface::FIELD_TYPE_DOUBLE;
            } elseif ($this->getBackendType($attributeId) == 'datetime') {
                $type = FieldInterface::FIELD_TYPE_DATE;
            } elseif ($this->usesSource($attributeId)) {
                $type = $attribute->getSourceModel() ? FieldInterface::FIELD_TYPE_KEYWORD : FieldInterface::FIELD_TYPE_INTEGER;
            }

            $this->attributeFieldTypeCache[$attributeId] = $type;
        }

        return $this->attributeFieldTypeCache[$attributeId];
    }

    /**
     * Parse attribute raw value (as saved in the database) to prepare the indexed value.
     * For attribute using options the option value is also added to the result which contains two keys :
     *   - one is "attribute_code" and contained the option id(s)
     *   - the other one is "option_text_attribute_code" and contained option value(s)
     * All value are transformed into arays to have a more simple management of
     * multivalued attributes merging on composite products).
     * ES doesn't care of having array of int when it an int is required.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param int     $attributeId Product attribute Id.
     * @param integer $storeId     Store id.
     * @param mixed   $value       Raw value to be parsed.
     *
     * @return array
     */
    public function prepareIndexValue($attributeId, $storeId, $value)
    {
        // Backward compatibility.
        if (!is_numeric($attributeId)) {
            $attributeId = $this->getAttributeId($attributeId);
        }

        $attributeCode = $this->getAttributeCodeById($attributeId);
        $values = [];

        $mapperKey = 'simple_' . $attributeId;
        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($value) use ($attributeId) {
                return $this->prepareSimpleIndexAttributeValue($attributeId, $value);
            };
        }

        if (!isset($this->attributeCleaners[$mapperKey])) {
            if ($this->includesZeroFalseValues($attributeId)) {
                // Filter empty values while keeping "0" (int or float) and "false" value.
                $this->attributeCleaners[$mapperKey] = function ($value) {
                    return (($value === false) || strlen($value));
                };
            } else {
                // Filter out empty values. Also removes "0" and "false" values.
                $this->attributeCleaners[$mapperKey] = function ($value) {
                    return !empty($value);
                };
            }
        }

        if ($this->usesSource($attributeId) && !is_array($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map($this->attributeMappers[$mapperKey], $value);
        $value = array_filter($value, $this->attributeCleaners[$mapperKey]);
        $value = array_values($value);
        $values[$attributeCode] = $value;

        if ($this->usesSource($attributeId)) {
            $optionTextFieldName = $this->getOptionTextFieldName($attributeCode);
            $optionTextValues    = $this->getIndexOptionsText($attributeId, $storeId, $value);
            // Filter empty values. Not using array_filter here because it could remove "0" string from values.
            $optionTextValues    = array_diff(array_map('trim', array_map('strval', $optionTextValues)), ['', null, false]);
            $optionTextValues    = array_values($optionTextValues);
            $values[$optionTextFieldName] = $optionTextValues;
        }

        return array_filter($values);
    }

    /**
     * Transform an array of options ids into an arrays of option values for attribute that uses a source.
     * Values are localized for a store id.
     *
     * @param int     $attributeId Product attribute Id.
     * @param integer $storeId     Store id
     * @param array   $optionIds   Array of options ids.
     *
     * @return array
     */
    public function getIndexOptionsText($attributeId, $storeId, array $optionIds)
    {
        $mapperKey = sprintf("options_%s_%s", $attributeId, $storeId);

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($optionId) use ($attributeId, $storeId) {
                return $this->getIndexOptionText($attributeId, $storeId, $optionId);
            };
        }

        $optionValues = array_map($this->attributeMappers[$mapperKey], $optionIds);

        return $optionValues;
    }

    /**
     * Transform an options id into an array of option value for attribute that uses a source.
     * Value islocalized for a store id.
     *
     * @param int            $attributeId Product attribute.
     * @param integer        $storeId     Store id.
     * @param string|integer $optionId    Option id.
     *
     * @return string|boolean
     */
    public function getIndexOptionText($attributeId, $storeId, $optionId)
    {
        $attribute = $this->getAttributeByStore($attributeId, $storeId);

        if (!isset($this->attributeOptionTextCache[$storeId]) || !isset($this->attributeOptionTextCache[$storeId][$attributeId])) {
            $this->attributeOptionTextCache[$storeId][$attributeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId][$attributeId][$optionId])) {
            $optionValue = $attribute->getSource()->getIndexOptionText($optionId);
            if ($this->getFieldType($attributeId) == FieldInterface::FIELD_TYPE_BOOLEAN) {
                $optionValue = null;
                if ($optionId == \Magento\Eav\Model\Entity\Attribute\Source\Boolean::VALUE_YES) {
                    $optionValue = $attribute->getStoreLabel($storeId);
                }
            }
            $this->attributeOptionTextCache[$storeId][$attributeId][$optionId] = $optionValue;
        }

        return $this->attributeOptionTextCache[$storeId][$attributeId][$optionId];
    }

    /**
     * Returns field use for filtering for an attribute.
     *
     * @param AttributeInterface $attribute Attribute.
     *
     * @return string
     */
    public function getFilterField(AttributeInterface $attribute)
    {
        $field = $attribute->getAttributeCode();

        // Do not use self::usesSource($attributeId) here.
        // This method is called in layered navigation with an already loaded attribute.
        // Going through $this->usesSource() would cause a reload of this unique attribute.
        // In layered navigation context, all filterable attributes goes through this, it would cause a huge overload.
        // Let's stick with the legacy usesSource() of attribute object.
        if ($attribute->usesSource()) {
            $field = $this->getOptionTextFieldName($field);
        }

        return $field;
    }

    /**
     * Ensure types of numerical values is correct before indexing.
     *
     * @param int   $attributeId Product attribute Id.
     * @param mixed $value       Raw value.
     *
     * @return mixed
     */
    private function prepareSimpleIndexAttributeValue($attributeId, $value)
    {
        if ($this->getFieldType($attributeId) == FieldInterface::FIELD_TYPE_BOOLEAN) {
            $value = boolval($value);
        } elseif ($this->getBackendType($attributeId) == 'decimal'
            || $this->getFrontendClass($attributeId) == 'validate-number') {
            $value = floatval($value);
        } elseif ($this->getBackendType($attributeId) == 'int') {
            $value = intval($value);
        }

        return $value;
    }

    /**
     * Load the localized version of an attribute.
     * This code uses a local cache to ensure correct performance during indexing.
     *
     * @param AttributeInterface|int $attribute Product attribute.
     * @param integer                $storeId   Store id.
     *
     * @return \Magento\Catalog\Api\Data\EavAttributeInterface
     */
    private function getAttributeByStore($attribute, $storeId)
    {
        $attributeId = $this->getAttributeId($attribute);

        if (!isset($this->storeAttributes[$storeId]) || !isset($this->storeAttributes[$storeId][$attributeId])) {
            /**
             * @var EavAttributeInterface
             */
            $storeAttribute = $this->attributeFactory->create();
            $storeAttribute->load($attributeId)->setStoreId($storeId);
            $this->storeAttributes[$storeId][$attributeId] = $storeAttribute;
        }

        return $this->storeAttributes[$storeId][$attributeId];
    }

    /**
     * Load an attribute by id.
     * This code uses a local cache to ensure correct performance during indexing.
     *
     * @param int $attributeId Product attribute id.
     *
     * @return \Magento\Catalog\Api\Data\EavAttributeInterface
     */
    private function getAttributeById($attributeId)
    {
        if (!isset($this->attributes[$attributeId])) {
            /**
             * @var EavAttributeInterface
             */
            $attribute = $this->attributeFactory->create();
            $attribute->load($attributeId);
            $this->attributes[$attributeId] = $attribute;
        }

        return $this->attributes[$attributeId];
    }

    /**
     * Load an attribute by id.
     * This code uses a local cache to ensure correct performance during indexing.
     *
     * @param int $attributeId Product attribute id.
     *
     * @return string
     */
    private function getAttributeCodeById($attributeId)
    {
        if (!isset($this->attributesCode[$attributeId])) {
            /**
             * @var EavAttributeInterface
             */
            $attribute = $this->getAttributeById($attributeId);
            $this->attributesCode[$attributeId] = $attribute->getAttributeCode();
        }

        return $this->attributesCode[$attributeId];
    }

    /**
     * This util method is used to ensure the attribute is an integer and uses it's id if it is an object.
     *
     * @param AttributeInterface|integer $attribute Product attribute.
     *
     * @return integer
     */
    private function getAttributeId($attribute)
    {
        $attributeId = $attribute;

        if (is_object($attribute)) {
            $attributeId = $attribute->getAttributeId();
        }

        return $attributeId;
    }

    /**
     * Compute result of $attribute->usesSource() into a local cache.
     * Mandatory because a lot of costly plugins (like in Swatches module) are plugged on this method.
     *
     * @param int $attributeId Attribute ID
     *
     * @return bool
     */
    private function usesSource($attributeId)
    {
        if (!isset($this->attributeUsesSourceCache[$attributeId])) {
            $attribute = $this->getAttributeById($attributeId);
            $this->attributeUsesSourceCache[$attributeId] = $attribute->usesSource();
        }

        return $this->attributeUsesSourceCache[$attributeId];
    }

    /**
     * Compute result of $attribute->getBackendType() into a local cache.
     * Mandatory because a lot of costly plugins (like in Swatches module) are plugged on this method.
     *
     * @param int $attributeId Attribute ID
     *
     * @return string|null
     */
    private function getBackendType($attributeId)
    {
        if (!isset($this->attributeBackendCache[$attributeId])) {
            $attribute = $this->getAttributeById($attributeId);
            $this->attributeBackendCache[$attributeId] = $attribute->getBackendType();
        }

        return $this->attributeBackendCache[$attributeId];
    }

    /**
     * Compute result of $attribute->getFrontendClass() into a local cache.
     *
     * @param int $attributeId Attribute ID
     *
     * @return string|null
     */
    private function getFrontendClass($attributeId)
    {
        if (!isset($this->attributeFrontendCache[$attributeId])) {
            $attribute = $this->getAttributeById($attributeId);
            $this->attributeFrontendCache[$attributeId] = $attribute->getFrontendClass();
        }

        return $this->attributeFrontendCache[$attributeId];
    }

    /**
     * Returns true if attribute allows indexing zero/false values.
     * Not using a local cache since it is only called once per attribute.
     *
     * @param int $attributeId Attribute ID
     *
     * @return bool
     */
    private function includesZeroFalseValues($attributeId)
    {
        return (bool) $this->getAttributeById($attributeId)->getIncludeZeroFalseValues();
    }
}
