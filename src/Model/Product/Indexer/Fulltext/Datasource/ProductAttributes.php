<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\ProductAttributes as ResourceModel;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Index\Mapping\FieldFactory;
use Smile\ElasticSuiteCatalog\Helper\ProductAttribute as ProductAttributeHelper;

class ProductAttributes implements DatasourceInterface, DynamicFieldProviderInterface
{
    /**
     * @var \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\ProductAttributes
     */
    private $resourceModel;

    /**
     * @var \Smile\ElasticSuiteCore\Index\Mapping\FieldFactory
     */
    private $fieldFactory;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $attributesById = [];

    /**
     * @var array
     */
    private $attributeIdsByTable = [];

    /**
     * @var array
     */
    private $optionTextCache = [];

    /**
     * @var \Smile\ElasticSuiteCatalog\Helper\ProductAttribute
     */
    private $attributeHelper;

    /**
     * @var array
     */
    private $authorizedBackendModels = [
        'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
        'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
        'Magento\Catalog\Model\Attribute\Backend\Startdate',
        'Magento\Catalog\Model\Product\Attribute\Backend\Boolean',
        'Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend'
    ];

    private $forbidenChildrenAttributeCode = [
        'visibility', 'status', 'price', 'tax_class_id'
    ];

    /**
     *
     * @param ResourceModel          $resourceModel
     * @param FieldFactory           $fieldFactory
     * @param ProductAttributeHelper $attributeHelper
     * @param array                  $authorizedBackendModels
     */
    public function __construct(
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        ProductAttributeHelper $attributeHelper,
        array $authorizedBackendModels = []
    )
    {
        $this->resourceModel   = $resourceModel;
        $this->attributeHelper = $attributeHelper;
        $this->fieldFactory    = $fieldFactory;

        if (is_array($authorizedBackendModels) && !empty($authorizedBackendModels)) {
            $authorizedBackendModels = array_values($authorizedBackendModels);
            $this->authorizedBackendModels = array_merge($authorizedBackendModels, $this->authorizedBackendModels);
        }

        $this->initAttributes();
    }

    /**
     *
     * @return \Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\ProductAttributes
     */
    private function initAttributes()
    {
        $attributeCollection = $this->attributeHelper->getAttibuteCollection();
        $this->resourceModel->addIndexedFilterToAttributeCollection($attributeCollection);

        foreach ($attributeCollection as $attribute) {
            if ($this->canIndexAttribute($attribute)) {
                $attributeId = (int) $attribute->getId();
                $this->attributesById[$attributeId] = $attribute;
                $this->attributeIdsByTable[$attribute->getBackendTable()][] = $attributeId;

                $this->initField($attribute);
            }
        }

        return $this;
    }

    /**
     *
     * @param ProductAttributeInterface $attribute
     *
     * @return boolean
     */
    private function canIndexAttribute(ProductAttributeInterface $attribute)
    {
        $canIndex = $attribute->getBackendType() != 'static';

        if ($canIndex && $attribute->getBackendModel()) {
            $canIndex = in_array($attribute->getBackendModel(), $this->authorizedBackendModels);
        }

        return $canIndex;
    }

    /**
     *
     * @param ProductAttributeInterface $attribute
     *
     * @return \Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\ProductAttributes
     */
    private function initField(ProductAttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();
        $fieldType = $this->attributeHelper->getFieldType($attribute);

        if ($attribute->usesSource()) {
            $fieldOptions = ['name' => $fieldName, 'type' => $fieldType, 'isSearchable' => false];
            $this->fields[$fieldName] = $this->fieldFactory->create($fieldOptions);
            $fieldName = $this->attributeHelper->getOptionTextFieldName($fieldName);
            $fieldType = 'string';
        }

        $fieldOptions = array_merge(
            ['name' => $fieldName, 'type'=> $fieldType],
            $this->attributeHelper->getMappingFieldOptions($attribute)
        );

        $this->fields[$fieldName] = $this->fieldFactory->create($fieldOptions);

        return $this;
    }

    /**
     * @inheritdoc
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface::getFields()
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @inheritdoc
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface::addData()
     */
    public function addData($storeId, array $indexData)
    {
        $productIds   = array_keys($indexData);
        $attributeIds = array_keys($this->attributesById);

        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $productIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $productId = (int) $row['entity_id'];
                $attribute = $this->attributesById[$row['attribute_id']];
                $indexData[$productId] += $this->attributeHelper->prepareIndexValue($attribute, $storeId, $row['value']);
            }
        }

        $parentIdsByChildrenId = $this->resourceModel->loadChildrenIds($productIds);
        $allChildrenIds = array_keys($parentIdsByChildrenId);

        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $allChildrenIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $attribute  = $this->attributesById[$row['attribute_id']];
                $childId    = (int) $row['entity_id'];
                $indexValue = null;
                foreach ($parentIdsByChildrenId[$childId] as $parentId) {
                    $canIndex = $this->canAddAttributeAsChild($attribute, $indexData[$parentId]['type_id']);
                    if ($canIndex) {
                        if ($indexValue === null) {
                            $indexValue = $this->attributeHelper->prepareIndexValue($attribute, $storeId, $row['value']);
                        }
                        $indexData[$parentId] = array_merge_recursive($indexData[$parentId], $indexValue);
                    }
                }
            }
        }

        return $indexData;
    }

    private function canAddAttributeAsChild(ProductAttributeInterface $attribute, $parentTypeId)
    {
        $canUseAsChild = !in_array($attribute->getAttributeCode(), $this->forbidenChildrenAttributeCode);

        if ($parentTypeId == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            if (!$attribute->getIsConfigurable()) {
                // @todo : implements
                $canUseAsChild = false;
            }
        }

        return $canUseAsChild;
    }

    /**
     * @param int    $storeId
     * @param array  $productIds
     * @param string $tableName
     * @param array  $attributeIds
     *
     * @return array
     */
    private function loadAttributesRawData($storeId, array $productIds, $tableName, array $attributeIds)
    {
        return $this->resourceModel->getAttributesRawData($storeId, $productIds, $tableName, $attributeIds);
    }
}
