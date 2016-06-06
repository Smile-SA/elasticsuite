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

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Indexer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;

/**
 * Abstract data source to retrieve attributes of EAV entities.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractAttributeData extends Indexer
{
    /**
     * @var array
     */
    protected $indexedAttributesConditions = [
        'is_searchable'                 => ['operator' => '=', 'value' => 1],
        'is_visible_in_advanced_search' => ['operator' => '=', 'value' => 1],
        'is_filterable'                 => ['operator' => '>', 'value' => 0],
        'is_filterable_in_search'       => ['operator' => '=', 'value' => 1],
        'is_used_for_promo_rules'       => ['operator' => '=', 'value' => 1],
        'used_for_sort_by'              => ['operator' => '=', 'value' => 1],
    ];

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
     * Load attribute data for a list of entity ids.
     *
     * @param int    $storeId      Store id.
     * @param array  $entityIds    Entity ids.
     * @param string $tableName    Attribute table.
     * @param array  $attributeIds Attribute ids to get loaded.
     *
     * @return array
     */
    public function getAttributesRawData($storeId, array $entityIds, $tableName, array $attributeIds)
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
            ->where('t_default.entity_id IN (?)', $entityIds)
            ->columns(['value' => new \Zend_Db_Expr('COALESCE(t_store.value, t_default.value)')]);

        return $this->connection->fetchAll($select);
    }
}
