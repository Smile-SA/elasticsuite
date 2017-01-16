<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Helper;

use Smile\ElasticsuiteCore\Helper\Mapping;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Attribute extends Mapping
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
    private $attributeOptionTextCache = [];

    /**
     * @var array
     */
    private $attributeMappers = [];

    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param Context                $context           Helper context.
     * @param ObjectManagerInterface $objectManager     The object manager
     * @param AttributeFactory       $attributeFactory  Factory used to create attributes.
     * @param string                 $collectionFactory Factory class name to use for create attribute collections.
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        AttributeFactory $attributeFactory,
        $collectionFactory
    ) {
        parent::__construct($context);
        $this->attributeFactory = $attributeFactory;
        $this->objectManager = $objectManager;
        $this->attributeCollectionFactory = $this->objectManager->get($collectionFactory);
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
        ];

        if ($attribute->getIsUsedInSpellcheck()) {
            $options['is_used_in_spellcheck'] = true;
        }

        if ($attribute->getIsUsedInAutocomplete()) {
            $options['is_used_in_autocomplete'] = true;
        }

        if ($attribute->getIsDisplayedInAutocomplete()) {
            $options['is_filterable'] = true;
        }

        return $options;
    }

    /**
     * Get mapping field type for an attribute.
     *
     * @param AttributeInterface $attribute Product attribute.
     *
     * @return string
     */
    public function getFieldType(AttributeInterface $attribute)
    {
        $type = FieldInterface::FIELD_TYPE_STRING;

        if ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean') {
            $type = FieldInterface::FIELD_TYPE_BOOLEAN;
        } elseif ($attribute->getBackendType() == 'int' || $attribute->getFrontendClass() == 'validate-digits') {
            $type = FieldInterface::FIELD_TYPE_INTEGER;
        } elseif ($attribute->getBackendType() == 'decimal' || $attribute->getFrontendClass() == 'validate-number') {
            $type = FieldInterface::FIELD_TYPE_DOUBLE;
        } elseif ($attribute->getBackendType() == 'datetime') {
            $type = FieldInterface::FIELD_TYPE_DATE;
        } elseif ($attribute->usesSource() && $attribute->getSourceModel() === null) {
            $type = FieldInterface::FIELD_TYPE_INTEGER;
        }

        return $type;
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
     * @param AttributeInterface $attribute Product attribute.
     * @param integer            $storeId   Store id.
     * @param mixed              $value     Raw value to be parsed.
     *
     * @return array
     */
    public function prepareIndexValue(AttributeInterface $attribute, $storeId, $value)
    {
        $attributeCode = $attribute->getAttributeCode();
        $values = [];

        $mapperKey = 'simple_' . $attribute->getId();

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($value) use ($attribute) {
                return $this->prepareSimpleIndexAttributeValue($attribute, $value);
            };
        }

        if ($attribute->usesSource() && !is_array($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_map($this->attributeMappers[$mapperKey], $value);
        $value = array_filter($value);
        $value = array_values($value);
        $values[$attributeCode] = $value;

        if ($attribute->usesSource()) {
            $optionTextFieldName = $this->getOptionTextFieldName($attributeCode);
            $optionTextValues    = $this->getIndexOptionsText($attribute, $storeId, $value);
            $optionTextValues    = array_filter($optionTextValues);
            $optionTextValues    = array_values($optionTextValues);
            $values[$optionTextFieldName] = $optionTextValues;
        }

        return array_filter($values);
    }

    /**
     * Transform an array of options ids into an arrays of option values for attribute that uses a source.
     * Values are localized for a store id.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @param integer            $storeId   Store id
     * @param array              $optionIds Array of options ids.
     *
     * @return array
     */
    public function getIndexOptionsText(AttributeInterface $attribute, $storeId, array $optionIds)
    {
        $mapperKey = sprintf("options_%s_%s", $attribute->getId(), $storeId);

        if (!isset($this->attributeMappers[$mapperKey])) {
            $this->attributeMappers[$mapperKey] = function ($optionId) use ($attribute, $storeId) {
                return $this->getIndexOptionText($attribute, $storeId, $optionId);
            };
        }

        $optionValues = array_map($this->attributeMappers[$mapperKey], $optionIds);

        return $optionValues;
    }

    /**
     * Transform an options id into an array of option value for attribute that uses a source.
     * Value islocalized for a store id.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @param integer            $storeId   Store id.
     * @param string|integer     $optionId  Option id.
     *
     * @return string|boolean
     */
    public function getIndexOptionText(AttributeInterface $attribute, $storeId, $optionId)
    {
        $attribute = $this->getAttributeByStore($attribute, $storeId);
        $attributeId = $attribute->getAttributeId();

        if (!isset($this->attributeOptionTextCache[$storeId]) || !isset($this->attributeOptionTextCache[$storeId][$attributeId])) {
            $this->attributeOptionTextCache[$storeId][$attributeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId][$attributeId][$optionId])) {
            $optionValue = $attribute->getSource()->getIndexOptionText($optionId);
            if ($this->getFieldType($attribute) == FieldInterface::FIELD_TYPE_BOOLEAN) {
                $optionValue = $attribute->getStoreLabel($storeId);
            }
            $this->attributeOptionTextCache[$storeId][$attributeId][$optionId] = $optionValue;
        }

        return $this->attributeOptionTextCache[$storeId][$attributeId][$optionId];
    }

    /**
     * Ensure types of numerical values is correct before indexing.
     *
     * @param AttributeInterface $attribute Product attribute.
     * @param mixed              $value     Raw value.
     *
     * @return mixed
     */
    private function prepareSimpleIndexAttributeValue(AttributeInterface $attribute, $value)
    {
        if ($attribute->getBackendType() == 'decimal') {
            $value = floatval($value);
        } elseif ($attribute->getBackendType() == 'int') {
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
}
