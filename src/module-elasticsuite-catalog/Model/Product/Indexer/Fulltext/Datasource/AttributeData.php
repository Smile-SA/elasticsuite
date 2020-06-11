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

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

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
    private $forbidenChildrenAttributeCode = [
        'visibility',
        'status',
        'price',
        'tax_class_id',
        'name',
    ];

    /**
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds   = array_keys($indexData);
        $indexData    = $this->addAttributeData($storeId, $productIds, $indexData);

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
                        $this->addChildData($indexData[$parentId], $childrenIndexData[$childId]);
                        $this->addChildSku($indexData[$parentId], $relation);
                    }
                }
            }
        }

        return $this->filterCompositeProducts($indexData);
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

                $this->addIndexedAttribute($indexData[$productId], $attribute);
            }
        }

        return $indexData;
    }

    /**
     * Append data of child products to the parent.
     *
     * @param array $parentData      Parent product data.
     * @param array $childAttributes Child product attributes data.
     *
     * @return void
     */
    private function addChildData(&$parentData, $childAttributes)
    {
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
            }

            $parentData[$attributeCode] = array_values(array_unique(array_merge($parentData[$attributeCode], $value)));
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

        if (!isset($parentData['children_attributes'])) {
            $parentData['children_attributes'] = ['indexed_attributes'];
        }

        $childrenAttributes = array_merge(
            $parentData['children_attributes'],
            array_diff($childAttributeCodes, $this->forbidenChildrenAttributeCode)
        );

        if (isset($relation['configurable_attributes']) && !empty($relation['configurable_attributes'])) {
            $attributesCodes = array_map(
                function (int $attributeId) {
                    if (isset($this->attributesById[$attributeId])) {
                        return $this->attributesById[$attributeId]->getAttributeCode();
                    }
                },
                $relation['configurable_attributes']
            );

            $parentData['configurable_attributes'] = array_values(
                array_unique(
                    array_merge($attributesCodes, $parentData['configurable_attributes'] ?? [])
                )
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
     * Append SKU of children product to the parent product index data.
     *
     * @param array $parentData Parent product data.
     * @param array $relation   Relation data between the child and the parent.
     */
    private function addChildSku(&$parentData, $relation)
    {
        if (isset($parentData['sku']) && !is_array($parentData['sku'])) {
            $parentData['sku'] = [$parentData['sku']];
        }

        $parentData['sku'][] = $relation['sku'];
        $parentData['sku'] = array_unique($parentData['sku']);
    }

    /**
     * Append an indexed attributes to indexed data of a given product.
     *
     * @param array                                                  $productIndexData Product Index data
     * @param \Magento\Eav\Model\Entity\Attribute\AttributeInterface $attribute        The attribute
     */
    private function addIndexedAttribute(&$productIndexData, $attribute)
    {
        if (!isset($productIndexData['indexed_attributes'])) {
            $productIndexData['indexed_attributes'] = [];
        }

        // Data can be missing for this attribute (Eg : due to null value being escaped,
        // or this attribute is already included in the array).
        if (isset($productIndexData[$attribute->getAttributeCode()])
            && !in_array($attribute->getAttributeCode(), $productIndexData['indexed_attributes'])
        ) {
            $productIndexData['indexed_attributes'][] = $attribute->getAttributeCode();
        }
    }
}
