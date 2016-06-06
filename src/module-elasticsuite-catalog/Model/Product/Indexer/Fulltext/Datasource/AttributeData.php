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

use Magento\Catalog\Api\Data\ProductAttributeInterface;
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
    private $forbidenChildrenAttributeCode = ['visibility', 'status', 'price', 'tax_class_id'];

    /**
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds   = array_keys($indexData);

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
