<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Helper\Attribute as ProductAttributeHelper;
use Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData as ResourceModel;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterface;
use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory;
use Smile\ElasticsuiteCore\Index\Mapping\FieldFactory;

/**
 * Datasource used to index product attributes.
 * This class is also used to generate attribute mapping since it implements DynamicFieldProviderInterface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeData extends AbstractAttributeData implements DatasourceInterface, DynamicFieldProviderInterface
{
    /**
     * @var array
     */
    private $forbidenChildrenAttributeCode = ['visibility', 'status', 'price', 'tax_class_id'];

    /**
     * @var ProductDataExtensionInterfaceFactory
     */
    private $dataExtensionInterfaceFactory;

    /**
     * AttributeData constructor.
     * @param ResourceModel                        $resourceModel                 ResourceModel
     * @param FieldFactory                         $fieldFactory                  Field Factory
     * @param ProductAttributeHelper               $attributeHelper               AttributeHelper
     * @param ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory DataExtension Factory
     * @param array                                $indexedBackendModels          Indexed Backend Models
     */
    public function __construct(
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        ProductAttributeHelper $attributeHelper,
        ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory,
        array $indexedBackendModels = []
    ) {
        parent::__construct($resourceModel, $fieldFactory, $attributeHelper, $indexedBackendModels);
        $this->dataExtensionInterfaceFactory = $dataExtensionInterfaceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds   = array_keys($indexData);
        $indexData    = $this->addAttributeData($storeId, $productIds, $indexData);

        // Add products data to service contract.
        $this->addProductData($storeId, $indexData);

        $relationsByChildId = $this->resourceModel->loadChildrens($productIds, $storeId);

        if (!empty($relationsByChildId)) {
            $allChildrenIds      = array_keys($relationsByChildId);
            $childrenIndexData   = $this->addAttributeData($storeId, $allChildrenIds);

            foreach ($childrenIndexData as $childrenId => $childrenData) {
                $enabled = isset($childrenData['status']) && current($childrenData['status']) == 1;
                if ($enabled === false) {
                    unset($childrenIndexData[$childrenId]);
                }
            }

            foreach ($relationsByChildId as $childId => $relations) {
                foreach ($relations as $relation) {
                    $parentId = (int) $relation['parent_id'];
                    if (isset($indexData[$parentId]) && isset($childrenIndexData[$childId])) {
                        $indexData[$parentId]['children_ids'][] = $childId;
                        $this->addRelationData($indexData[$parentId], $childrenIndexData[$childId], $relation);
                        $this->addChildData($storeId, $indexData[$parentId], $childrenIndexData[$childId], $childId);
                    }
                }
            }
        }

        $indexData = $this->filterCompositeProducts($indexData);

        return $indexData;
    }

    /**
     * Append attribute data to the index.
     *
     * @param int   $storeId    Indexed store id.
     * @param array $productIds Indexed product ids.
     * @param array $indexData  Original indexed data.
     *
     * @return array
     */
    private function addAttributeData($storeId, $productIds, $indexData = [])
    {
        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $productIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $productId    = (int) $row['entity_id'];
                $attribute    = $this->attributesById[$row['attribute_id']];
                $indexValues  = $this->attributeHelper->prepareIndexValue($attribute, $storeId, $row['value']);
                if (!isset($indexData[$productId])) {
                    $indexData[$productId] = [];
                }
                $indexData[$productId] += $indexValues;
            }
        }

        return $indexData;
    }

    /**
     * Returns DataExtension object for productData array
     *
     * @param array $product Product Array
     *
     * @return ProductDataExtensionInterface
     */
    private function getDataExtension(&$product)
    {
        if (!isset($product[ProductDataExtensionInterface::KEY])) {
            $product[ProductDataExtensionInterface::KEY]
                = $this->dataExtensionInterfaceFactory->create();
        }

        return $product[ProductDataExtensionInterface::KEY];
    }

    /**
     * Append data of child products to the parent.
     *
     * @param int   $storeId         Store id
     * @param array $parentData      Parent product data.
     * @param array $childAttributes Child product attributes data.
     * @param int   $childId         Child product id
     *
     * @return void
     */
    private function addChildData($storeId, &$parentData, $childAttributes, $childId)
    {
        // Add child data to service contract of parent product.
        $this->getDataExtension($parentData)
             ->addChildData($storeId, $childAttributes, $childId);
        $authorizedChildAttributes = $parentData['children_attributes'];
        $addedChildAttributesData  = array_filter(
            $childAttributes,
            function ($attributeCode) use ($authorizedChildAttributes) {
                return in_array($attributeCode, $authorizedChildAttributes);
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($addedChildAttributesData as $attributeCode => $value) {
            if (!isset($parentData[$attributeCode])) {
                $parentData[$attributeCode] = [];
            } elseif ($attributeCode != ProductDataExtensionInterface::KEY) {
                // Copy all except attribute data extension to parent.
                $parentData[$attributeCode] = array_values(array_unique(array_merge($parentData[$attributeCode], $value)));
            }
        }
    }

    /**
     * Append relation information to the index for composite products.
     *
     * @param array $parentData      Parent product data.
     * @param array $childAttributes Child product attributes data.
     * @param array $relation        Relation data between the child and the parent.
     *
     * @return void
     */
    private function addRelationData(&$parentData, $childAttributes, $relation)
    {
        $childAttributeCodes  = array_keys($childAttributes);
        $parentAttributeCodes = array_keys($parentData);

        if (!isset($parentData['children_attributes'])) {
            $parentData['children_attributes'] = [];
        }

        $childrenAttributes = array_merge(
            $parentData['children_attributes'],
            array_diff($childAttributeCodes, $this->forbidenChildrenAttributeCode)
        );

        if (isset($relation['configurable_attributes']) && !empty($relation['configurable_attributes'])) {
            $addedChildrenAttributes = array_diff(
                $childAttributeCodes,
                $this->forbidenChildrenAttributeCode,
                $parentAttributeCodes
            );
            $childrenAttributes = array_merge($addedChildrenAttributes, $parentData['children_attributes']);

            if (!isset($parentData['configurable_attributes'])) {
                $parentData['configurable_attributes'] = [];
            }

            $configurableAttributesCodes = array_map(
                function ($attributeId) {
                    if (isset($this->attributesById[(int) $attributeId])) {
                        return $this->attributesById[(int) $attributeId]->getAttributeCode();
                    }
                },
                $relation['configurable_attributes']
            );

            $parentData['configurable_attributes'] = array_values(
                array_unique(array_merge($configurableAttributesCodes, $parentData['configurable_attributes']))
            );
        }

        $parentData['children_attributes'] = array_values(array_unique($childrenAttributes));
    }

    /**
     * Filter out composite product when no enabled children are attached.
     *
     * @param array $indexData Indexed data.
     *
     * @return array
     */
    private function filterCompositeProducts($indexData)
    {
        $compositeProductTypes = $this->resourceModel->getCompositeTypes();

        foreach ($indexData as $productId => $productData) {
            $isComposite = in_array($productData['type_id'], $compositeProductTypes);
            $hasChildren = isset($productData['children_ids']) && !empty($productData['children_ids']);
            if ($isComposite && !$hasChildren) {
                unset($indexData[$productId]);
            }
        }

        return $indexData;
    }

    /**
     * Adds product data to service contract
     *
     * @param int   $storeId   Store ID
     * @param array $indexData Index data
     *
     * @return $this
     */
    private function addProductData($storeId, &$indexData)
    {
        foreach ($indexData as &$product) {
            $this->getDataExtension($product)
                ->addProductData($storeId, $product);
        }

        return $this;
    }
}
