<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData as ResourceModel;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Smile\ElasticSuiteCore\Index\Mapping\FieldFactory;
use Smile\ElasticSuiteCatalog\Helper\ProductAttribute as ProductAttributeHelper;

/**
 * Datasource used to index product attributes.
 * This class is also used to generate attribute mapping since it implements DynamicFieldProviderInterface.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeData implements DatasourceInterface, DynamicFieldProviderInterface
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
     * @var \Smile\ElasticSuiteCatalog\Helper\ProductAttribute
     */
    private $attributeHelper;

    /**
     * @var array
     */
    private $indexedBackendModels = [
        'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
        'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
        'Magento\Catalog\Model\Attribute\Backend\Startdate',
        'Magento\Catalog\Model\Product\Attribute\Backend\Boolean',
        'Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend',
    ];

    /**
     * @var array
     */
    private $forbidenChildrenAttributeCode = ['visibility', 'status', 'price', 'tax_class_id'];

    /**
     * Constructor
     *
     * @param ResourceModel          $resourceModel        Resource model.
     * @param FieldFactory           $fieldFactory         Mapping field factory.
     * @param ProductAttributeHelper $attributeHelper      Attribute helper.
     * @param array                  $indexedBackendModels List of indexed backend models added to the default list.
     */
    public function __construct(
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        ProductAttributeHelper $attributeHelper,
        array $indexedBackendModels = []
    ) {
        $this->resourceModel   = $resourceModel;
        $this->attributeHelper = $attributeHelper;
        $this->fieldFactory    = $fieldFactory;

        if (is_array($indexedBackendModels) && !empty($indexedBackendModels)) {
            $indexedBackendModels = array_values($indexedBackendModels);
            $this->indexedBackendModels = array_merge($indexedBackendModels, $this->indexedBackendModels);
        }

        $this->initAttributes();
    }

    /**
     * List of fields generated from the attributes list.
     * This list is used to generate the catalog_product ES mapping.
     *
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
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

                $indexValues = $this->attributeHelper->prepareIndexValue(
                    $attribute,
                    $storeId,
                    $row['value']
                );

                $indexData[$productId] += $indexValues;
            }
        }

        $relationsByChildrenId = $this->resourceModel->loadChildrens($productIds);
        $allChildrenIds = array_keys($relationsByChildrenId);

        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $allChildrenIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $attribute  = $this->attributesById[$row['attribute_id']];
                $childId    = (int) $row['entity_id'];

                foreach ($relationsByChildrenId[$childId] as $relationByChildren) {
                    $parentId = $relationByChildren['parent_id'];
                    $this->addRelationData($indexData[$parentId], $childId, $relationByChildren);
                    $this->addChildrenData(
                        $indexData[$parentId],
                        $attribute,
                        $storeId,
                        $relationByChildren,
                        $row
                    );
                }
            }
        }

        return $indexData;
    }


    /**
     * Init attributes used into ES.
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
     * Check if an attribute can be indexed.
     *
     * @param ProductAttributeInterface $attribute Product attribute.
     *
     * @return boolean
     */
    private function canIndexAttribute(ProductAttributeInterface $attribute)
    {
        $canIndex = $attribute->getBackendType() != 'static';

        if ($canIndex && $attribute->getBackendModel()) {
            $canIndex = in_array($attribute->getBackendModel(), $this->indexedBackendModels);
        }

        return $canIndex;
    }

    /**
     * Create a mapping field from an attribute.
     *
     * @param ProductAttributeInterface $attribute Product attribute.
     *
     * @return \Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Datasource\ProductAttributes
     */
    private function initField(ProductAttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();
        $fieldType = $this->attributeHelper->getFieldType($attribute);

        $fieldConfig = $this->attributeHelper->getMappingFieldOptions($attribute);

        if ($attribute->usesSource()) {
            $fieldConfig = $this->attributeHelper->getMappingFieldOptions($attribute);
            $fieldConfig['is_searchable'] = false;
            $fieldConfig['is_used_in_spellcheck'] = false;
            $fieldConfig['is_used_in_autocomplete'] = false;
            $fieldOptions = ['name' => $fieldName, 'type' => $fieldType, 'fieldConfig' => $fieldConfig];
            $this->fields[$fieldName] = $this->fieldFactory->create($fieldOptions);
            $fieldName = $this->attributeHelper->getOptionTextFieldName($fieldName);
            $fieldType = 'string';

            $fieldConfig['is_searchable'] = true;
        }

        $fieldOptions = ['name' => $fieldName, 'type' => $fieldType, 'fieldConfig' => $fieldConfig];

        $this->fields[$fieldName] = $this->fieldFactory->create($fieldOptions);

        return $this;
    }

    /**
     * Check if an attribute can be indexed when used as a children/
     *
     * @param ProductAttributeInterface $attribute          Product attribute.
     * @param array                     $relationByChildren The relation data based on children
     * @param string                    $parentTypeId       Parent product type id.
     *
     * @return boolean
     */
    private function canAddAttributeAsChild(ProductAttributeInterface $attribute, $relationByChildren, $parentTypeId)
    {
        $canUseAsChild = !in_array($attribute->getAttributeCode(), $this->forbidenChildrenAttributeCode);

        if ($parentTypeId == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            if (isset($relationByChildren['configurable_attributes'])
            && (!in_array($attribute->getAttributeId(), $relationByChildren['configurable_attributes']))
            ) {
                $canUseAsChild = false;
            }
        }

        return $canUseAsChild;
    }

    /**
     * Load attribute data from the database.
     *
     * @param integer $storeId      Store id.
     * @param array   $productIds   Product ids.
     * @param string  $tableName    Attribute table name.
     * @param array   $attributeIds Loaded attribute ids.
     *
     * @return array
     */
    private function loadAttributesRawData($storeId, array $productIds, $tableName, array $attributeIds)
    {
        return $this->resourceModel->getAttributesRawData($storeId, $productIds, $tableName, $attributeIds);
    }

    /**
     * Append relation data to parent product
     *
     * @param array $parentIndexData Index Data for parent product
     * @param int   $childrenId      The Children Id
     * @param array $relationByChild Relation data for child
     */
    private function addRelationData(array &$parentIndexData, $childrenId, array $relationByChild)
    {
        if (!isset($parentIndexData['children_ids'])) {
            $parentIndexData['children_ids'] = [];
        }
        if (!in_array($childrenId, $parentIndexData['children_ids'])) {
            $parentIndexData['children_ids'][] = $childrenId;
        }

        if (isset($relationByChild['configurable_attributes'])) {
            foreach ($relationByChild['configurable_attributes'] as $attributeId) {
                $attributeCode = $this->attributesById[(int) $attributeId]->getAttributeCode();
                if (!isset($parentIndexData['configurable_attributes'])) {
                    $parentIndexData['configurable_attributes'] = [];
                }
                if (!in_array($attributeCode, $parentIndexData['configurable_attributes'])) {
                    $parentIndexData['configurable_attributes'][] = $attributeCode;
                }
            }
        }
    }

    /**
     * Append children data to parent product
     *
     * @param array                     $parentIndexData    Index data of the parent product
     * @param ProductAttributeInterface $attribute          The attribute
     * @param int                       $storeId            The store Id
     * @param array                     $relationByChildren The relation data
     * @param array                     $row                The value row for children
     */
    private function addChildrenData(&$parentIndexData, $attribute, $storeId, $relationByChildren, $row)
    {
        $canIndex = $this->canAddAttributeAsChild(
            $attribute,
            $relationByChildren,
            $parentIndexData['type_id']
        );

        if ($canIndex) {
            $indexValues = $this->attributeHelper->prepareIndexValue(
                $attribute,
                $storeId,
                $row['value']
            );

            $parentIndexData = array_merge_recursive($parentIndexData, $indexValues);

            foreach (array_keys($indexValues) as $fieldKey) {
                if (isset($parentIndexData[$fieldKey])) {
                    $parentIndexData[$fieldKey] = array_values(array_unique($parentIndexData[$fieldKey]));
                }
            }
        }
    }
}
