<?php

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\AbstractIndexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\Product\Type as ProductType;

class ProductAttributes extends AbstractIndexer
{

    /**
     * Catalog product type
     *
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $catalogProductType;

    /**
     * @var \Magento\Catalog\Model\Product\Type[]
     */
    private $productTypes = [];

    /**
     * @var array
     */
    private $productEmulators = [];

    /**
     *
     * @var unknown
     */
    private $indexedAttributesConditions = [
        'is_searchable'                 => ['operator' => '=', 'value' => 1],
        'is_visible_in_advanced_search' => ['operator' => '=', 'value' => 1],
        'is_filterable'                 => ['operator' => '>', 'value' => 0],
        'is_filterable_in_search'       => ['operator' => '=', 'value' => 1],
        'used_for_sort_by'              => ['operator' => '=', 'value' => 1],
        'used_for_sort_by'              => ['operator' => '=', 'value' => 1]
    ];

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        ProductType $catalogProductType
    ) {
        parent::__construct($resource, $storeManager);
        $this->catalogProductType = $catalogProductType;
    }

    /**
     *
     * @param AttributeCollection $attributeCollection
     *
     * @return AttributeCollection
     */
    public function addIndexedFilterToAttributeCollection(AttributeCollection $attributeCollection)
    {
        $conditions = [];

        foreach ($this->indexedAttributesConditions as $fieldName => $condition) {
            if ($condition['operator'] == 'IN' || is_array($condition['value'])) {
                $conditionString = sprintf('%s %s (?)', $fieldName, $condition['operator']);
                $conditions[] = $this->connection->quoteInto($conditionString, $condition['value']);
            } else {
                $conditions[] = sprintf('%s %s %s', $fieldName, $condition['operator'], $condition['value']);
            }
        }

        $select = $attributeCollection->getSelect();
        $select->where(implode(' OR ', $conditions));

        return $attributeCollection;
    }

    /**
     *
     * @param int    $storeId
     * @param array  $productIds
     * @param string $tableName
     * @param array  $attributeIds
     *
     * @return array
     */
    public function getAttributesRawData($storeId, array $productIds, $tableName, array $attributeIds)
    {
        $select  = $this->connection->select();

        $joinStoreValuesConditionClauses = [
            't_default.entity_id = t_store.entity_id',
             't_default.attribute_id = t_store.attribute_id',
             't_store.store_id= ?',
        ];

        $joinStoreValuesCondition = $this->connection->quoteInto(
            implode(' AND ', $joinStoreValuesConditionClauses),
            $storeId
        );

        $select->from(['t_default' => $tableName], ['entity_id', 'attribute_id'])
            ->joinLeft(['t_store' => $tableName], $joinStoreValuesCondition, [])
            ->where('t_default.store_id=?', 0)
            ->where('t_default.attribute_id IN (?)', $attributeIds)
            ->where('t_default.entity_id IN (?)', $productIds)
            ->columns(['value' => new \Zend_Db_Expr('COALESCE(t_store.value, t_default.value)')]);

        return $this->connection->fetchAll($select);
    }

    /**
     *
     * @param array $productIds
     */
    public function loadChildrenIds($productIds)
    {
        $children = [];

        foreach ($this->catalogProductType->getCompositeTypes() as $productTypeId) {
            $typeInstance = $this->getProductTypeInstance($productTypeId);
            $relation = $typeInstance->getRelationInfo();

            if ($relation->getTable() && $relation->getParentFieldName() && $relation->getChildFieldName()) {

                $relationTable   = $this->getTable($relation->getTable());
                $parentFieldName = $relation->getParentFieldName();
                $childFieldName  = $relation->getChildFieldName();

                $select = $this->getConnection()->select()
                    ->from(['main' => $relationTable], [$parentFieldName, $childFieldName])
                    ->where("main.{$parentFieldName} in (?)", $productIds);

                if (!is_null($relation->getWhere())) {
                    $select->where($relation->getWhere());
                }

                $data = $this->getConnection()->fetchAll($select);

                foreach ($data as $relationRow) {
                    $parentId = (int) $relationRow[$parentFieldName];
                    $childId  = (int) $relationRow[$childFieldName];
                    $children[$childId][] = $parentId;
                }
            }
        }

        return $children;
    }


    /**
     * Retrieve Product Emulator (Magento Object)
     *
     * @param   string $typeId
     * @return \Magento\Framework\DataObject
     */
    protected function getProductEmulator($typeId)
    {
        if (!isset($this->productEmulators[$typeId])) {
            $productEmulator = new \Magento\Framework\DataObject();
            $productEmulator->setTypeId($typeId);
            $this->productEmulators[$typeId] = $productEmulator;
        }
        return $this->productEmulators[$typeId];
    }

    /**
     * Retrieve Product Type Instance
     *
     * @param string $typeId
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     */
    protected function getProductTypeInstance($typeId)
    {
        if (!isset($this->productTypes[$typeId])) {
            $productEmulator = $this->getProductEmulator($typeId);

            $this->productTypes[$typeId] = $this->catalogProductType->factory($productEmulator);
        }
        return $this->productTypes[$typeId];
    }
}
