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

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer\AbstractIndexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Attributes datasource resource model.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeData extends AbstractIndexer
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
     * @var array
     */
    private $indexedAttributesConditions = [
        'is_searchable'                 => ['operator' => '=', 'value' => 1],
        'is_visible_in_advanced_search' => ['operator' => '=', 'value' => 1],
        'is_filterable'                 => ['operator' => '>', 'value' => 0],
        'is_filterable_in_search'       => ['operator' => '=', 'value' => 1],
        'used_for_sort_by'              => ['operator' => '=', 'value' => 1],
        'used_for_sort_by'              => ['operator' => '=', 'value' => 1],
    ];

    /**
     * Constructor.
     *
     * @param ResourceConnection    $resource           Database adpater.
     * @param StoreManagerInterface $storeManager       Store manager.
     * @param ProductType           $catalogProductType Product type.
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
     * Allow to filter an attribute collection on attributes that are indexed into the search engine.
     *
     * @param AttributeCollection $attributeCollection Attribute collection (not loaded).
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
            } elseif (!is_array($condition['value'])) {
                $conditions[] = sprintf('%s %s %s', $fieldName, $condition['operator'], $condition['value']);
            }
        }

        if (!empty($conditions)) {
            $select = $attributeCollection->getSelect();
            $select->where(implode(' OR ', $conditions));
        }

        return $attributeCollection;
    }

    /**
     * Load attribute data for a list of product ids.
     *
     * @param int    $storeId      Store id.
     * @param array  $productIds   Product ids.
     * @param string $tableName    Attribute table.
     * @param array  $attributeIds Attribute ids to get loaded.
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
     * Retrieve list of children ids for a product list.
     *
     * Warning the result use children ids as a key and list of parents as value
     *
     * @param array $productIds List of parent product ids.
     *
     * @return array
     */
    public function loadChildrens($productIds)
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

                if ($relation->getWhere() !== null) {
                    $select->where($relation->getWhere());
                }

                $configurationTable   = $this->getTable("catalog_product_super_attribute");
                $configurableAttrExpr = "GROUP_CONCAT(DISTINCT super_table.attribute_id SEPARATOR ',')";

                $select->joinLeft(
                    ["super_table" => $configurationTable],
                    "super_table.product_id = main.{$parentFieldName}",
                    ["configurable_attributes" => new \Zend_Db_Expr($configurableAttrExpr)]
                );

                $select->group("main.{$childFieldName}");

                $data = $this->getConnection()->fetchAll($select);

                foreach ($data as $relationRow) {
                    $parentId = (int) $relationRow[$parentFieldName];
                    $childId  = (int) $relationRow[$childFieldName];
                    $configurableAttributes = array_filter(explode(',', $relationRow["configurable_attributes"]));
                    $children[$childId][] = [
                        "parent_id"               => $parentId,
                        "configurable_attributes" => $configurableAttributes,
                    ];
                }
            }
        }

        return $children;
    }


    /**
     * Retrieve Product Emulator (Magento Object) by type identifier.
     *
     * @param string $typeId Type identifier.
     *
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
     * Retrieve product type instance from identifier.
     *
     * @param string $typeId Type identifier.
     *
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
