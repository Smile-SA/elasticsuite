<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData as ResourceModel;
use Smile\ElasticsuiteCore\Index\Mapping\FieldFactory;
use Smile\ElasticsuiteCatalog\Helper\Attribute as ProductAttributeHelper;

/**
 * Abstract Datasource for EAV entities
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractAttributeData
{
    /**
     * @var array
     */
    protected $attributesById = [];

    /**
     * @var array
     */
    protected $attributeIdsByTable = [];

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\Attribute
     */
    protected $attributeHelper;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData
     */
    protected $resourceModel;

    /**
     * @var \Smile\ElasticsuiteCore\Index\Mapping\FieldFactory
     */
    protected $fieldFactory;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $indexedBackendModels = [
        'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
        'Magento\Eav\Model\Entity\Attribute\Backend\Datetime',
        'Magento\Catalog\Model\Attribute\Backend\Startdate',
        'Magento\Catalog\Model\Product\Attribute\Backend\Boolean',
        'Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend',
        'Magento\Catalog\Model\Product\Attribute\Backend\Weight',
    ];

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
     * Load attribute data from the database.
     *
     * @param integer $storeId      Store id.
     * @param array   $entityIds    Entity ids.
     * @param string  $tableName    Attribute table name.
     * @param array   $attributeIds Loaded attribute ids.
     *
     * @return array
     */
    protected function loadAttributesRawData($storeId, array $entityIds, $tableName, array $attributeIds)
    {
        return $this->resourceModel->getAttributesRawData($storeId, $entityIds, $tableName, $attributeIds);
    }

    /**
     * Init attributes used into ES.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData
     */
    private function initAttributes()
    {
        $attributeCollection = $this->attributeHelper->getAttributeCollection();
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
     * @param AttributeInterface $attribute Entity attribute.
     *
     * @return boolean
     */
    private function canIndexAttribute(AttributeInterface $attribute)
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
     * @param AttributeInterface $attribute Entity attribute.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Catalog\Indexer\Fulltext\Datasource\AbstractAttributeData
     */
    private function initField(AttributeInterface $attribute)
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
}
