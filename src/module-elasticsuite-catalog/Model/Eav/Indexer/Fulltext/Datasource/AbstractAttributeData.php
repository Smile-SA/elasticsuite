<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData as ResourceModel;
use Smile\ElasticsuiteCore\Index\Mapping\FieldFactory;
use Smile\ElasticsuiteCatalog\Helper\AbstractAttribute as AttributeHelper;
use Smile\ElasticsuiteCatalog\Scope\Config;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Abstract Datasource for EAV entities
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
abstract class AbstractAttributeData
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
     * @var \Smile\ElasticsuiteCatalog\Helper\AbstractAttribute
     */
    protected $attributeHelper;

    /** @var Config */
    protected $config;

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
        \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
        \Magento\Catalog\Model\Attribute\Backend\Startdate::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
        \Magento\Eav\Model\Entity\Attribute\Backend\DefaultBackend::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Weight::class,
        \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
    ];

    /**
     * Constructor
     *
     * @param Config          $config               Configuration retriever
     * @param ResourceModel   $resourceModel        Resource model.
     * @param FieldFactory    $fieldFactory         Mapping field factory.
     * @param AttributeHelper $attributeHelper      Attribute helper.
     * @param array           $indexedBackendModels List of indexed backend models added to the default list.
     */
    public function __construct(
        Config $config,
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        AttributeHelper $attributeHelper,
        array $indexedBackendModels = []
    ) {
        $this->config          = $config;
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

            if ($attribute->getAttributeCode() === 'sku') {
                // SKU has no backend table.
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
        // 'price' attribute is declared as nested field into the indices file.
        $canIndex = $attribute->getBackendType() != 'static' && $attribute->getAttributeCode() !== 'price';

        if ($canIndex && $attribute->getBackendModel()) {
            foreach ($this->indexedBackendModels as $indexedBackendModel) {
                $canIndex = is_a($attribute->getBackendModel(), $indexedBackendModel, true);
                if ($canIndex) {
                    return $canIndex;
                }
            }
        }

        return $canIndex;
    }

    /**
     * Create a mapping field from an attribute.
     *
     * @param AttributeInterface $attribute Entity attribute.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData
     */
    private function initField(AttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();
        $fieldConfig = $this->attributeHelper->getMappingFieldOptions($attribute);

        if ($attribute->usesSource()) {
            $optionFieldName = $this->attributeHelper->getOptionTextFieldName($fieldName);
            $fieldType = FieldInterface::FIELD_TYPE_TEXT;
            $fieldOptions = ['name' => $optionFieldName, 'type' => $fieldType, 'fieldConfig' => $fieldConfig];
            $this->fields[$optionFieldName] = $this->fieldFactory->create($fieldOptions);

            // Reset parent field values : only the option text field should be used for spellcheck and autocomplete.
            $fieldConfig['is_used_in_spellcheck'] = false;
            $fieldConfig['is_searchable'] = false;
        }

        $fieldType    = $this->attributeHelper->getFieldType($attribute->getId());
        $fieldOptions = ['name' => $fieldName, 'type' => $fieldType, 'fieldConfig' => $fieldConfig];

        $this->fields[$fieldName] = $this->fieldFactory->create($fieldOptions);

        return $this;
    }
}
