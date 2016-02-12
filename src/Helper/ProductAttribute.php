<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Helper;

use Smile\ElasticSuiteCore\Helper\Mapping;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Magento\ConfigurableProduct\Controller\Adminhtml\Product\Attribute\GetAttributes;

/**
 *
 *
 */
class ProductAttribute extends Mapping
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
     *
     * @param \Magento\Framework\App\Helper\Context                                    $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory                $attributeFactory
     */
    public function __construct(Context $context, AttributeCollectionFactory $attributeCollectionFactory, AttributeFactory $attributeFactory)
    {
        parent::__construct($context);
        $this->attributeFactory           = $attributeFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     *
     * @return Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttibuteCollection()
    {
        return $this->attributeCollectionFactory->create();
    }

    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute
     *
     * @return array
     */
    public function getMappingFieldOptions(ProductAttributeInterface $attribute)
    {
        $options = [
            'isSearchable'         => $attribute->getIsSearchable(),
            'isFilterable'         => $attribute->getIsFilterable(),
            'isFilterableInSearch' => $attribute->getIsFilterable(),
            'searchWeight'         => $attribute->getSearchWeight(),
        ];

        return $options;
    }

    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute
     *
     * @return string
     */
    public function getFieldType(ProductAttributeInterface $attribute)
    {
        $type = FieldInterface::FIELD_TYPE_STRING;

        if ($attribute->getBackendType() == 'int' || $attribute->getFrontendClass() == 'validate-digits') {
            $type = FieldInterface::FIELD_TYPE_INTEGER;
        } elseif ($attribute->getBackendType() == 'decimal' || $attribute->getFrontendClass() == 'validate-number') {
            $type = FieldInterface::FIELD_TYPE_DOUBLE;
        } elseif ($attribute->getSourceModel() == 'eav/entity_attribute_source_boolean') {
            $type = FieldInterface::FIELD_TYPE_BOOLEAN;
        } elseif ($attribute->getBackendType() == 'datetime') {
            $type = FieldInterface::FIELD_TYPE_DATE;
        } elseif ($attribute->usesSource() && $attribute->getSourceModel() === null) {
            $type = FieldInterface::FIELD_TYPE_INTEGER;
        }

        return $type;
    }

    /**
     *
     * @param ProductAttributeInterface $attribute
     * @param unknown $storeId
     * @param mixed $value
     *
     * @return array
     */
    public function prepareIndexValue(ProductAttributeInterface $attribute, $storeId, $value)
    {
        $attributeCode = $attribute->getAttributeCode();
        $values = [];

        $simpleValueMapper = function($value) use ($attribute) {
            return $this->prepareSimpleIndexAttributeValue($attribute, $value);
        };

        if ($attribute->usesSource() && !is_array($value)) {
            $value = explode(',', $value);
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $values[$attributeCode] = $value = array_filter(array_map($simpleValueMapper, $value));

        if ($attribute->usesSource()) {
            $optionTextFieldName = $this->getOptionTextFieldName($attributeCode);
            $values[$optionTextFieldName] = array_filter($this->getIndexOptionsText($attribute, $storeId, $value));
        }

        return array_filter($values);
    }

    /**
     *
     * @param ProductAttributeInterface $attribute
     * @param unknown $storeId
     * @param unknown $value
     * @return number
     */
    private function prepareSimpleIndexAttributeValue(ProductAttributeInterface $attribute, $value)
    {
        if ($attribute->getBackendType() == 'decimal') {
            $value = floatval($value);
        } else if ($attribute->getBackendType() == 'int') {
            $value = intval($value);
        }
        return $value;
    }


    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute
     * @param int                                                 $storeId
     * @param array                                               $optionIds
     *
     * @return array
     */
    public function getIndexOptionsText(ProductAttributeInterface $attribute, $storeId, array $optionIds)
    {
        $mapper = function($optionId) use($attribute, $storeId) {
            return $this->getIndexOptionText($attribute, $storeId, $optionId);
        };
        $optionValues = array_map($mapper, $optionIds);
        return $optionValues;
    }

    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute
     * @param int                                                 $storeId
     * @param int                                                 $optionId
     *
     * @return string|bool
     */
    public function getIndexOptionText(ProductAttributeInterface $attribute, $storeId, $optionId)
    {
        $attribute   = $this->getAttributeByStore($attribute, $storeId);
        $attributeId = $attribute->getAttributeId();

        if (!isset($this->attributeOptionTextCache[$storeId])) {
            $this->attributeOptionTextCache[$storeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId])) {
            $this->attributeOptionTextCache[$storeId][$attributeId] = [];
        }

        if (!isset($this->attributeOptionTextCache[$storeId][$attributeId][$optionId])) {
            $optionValue = $attribute->getSource()->getIndexOptionText($optionId);
            $this->attributeOptionTextCache[$storeId][$attributeId][$optionId] = $optionValue;
        }

        return $this->attributeOptionTextCache[$storeId][$attributeId][$optionId];
    }

    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface|int $attribute
     * @param int                           $storeId
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function getAttributeByStore($attribute, $storeId)
    {
        $storeAttribute = false;
        $attributeId = $this->getAttributeId($attribute);

        if (!isset($this->storeAttributes[$storeId]) || !isset($this->storeAttributes[$storeId][$attributeId])) {
            /**
             * @var ProductAttributeInterface
             */
            $storeAttribute = $this->attributeFactory->create();
            $storeAttribute->setStoreId($storeId)
                ->load($attributeId);

            $this->storeAttributes[$storeId][$attributeId] = $storeAttribute;
        }

        return $this->storeAttributes[$storeId][$attributeId];
    }

    /**
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface|int $attribute
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
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