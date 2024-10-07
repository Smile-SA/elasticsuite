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

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
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
     * @var null|string
     */
    private $entityTypeId = null;

    /**
     * AbstractAttributeData constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection     $resource     Resource Connection
     * @param \Magento\Store\Model\StoreManagerInterface    $storeManager Store Manager
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool Entity Metadata Pool
     * @param string                                        $entityType   Entity Type
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        $entityType = null
    ) {
        $this->entityTypeId = $entityType;
        parent::__construct($resource, $storeManager, $metadataPool);
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
        // The field modelizing the link between entity table and attribute values table. Either row_id or entity_id.
        $linkField = $this->getEntityMetaData($this->getEntityTypeId())->getLinkField();

        // The legacy entity_id field.
        $entityIdField = $this->getEntityMetaData($this->getEntityTypeId())->getIdentifierField();
        $entityTable   = $this->getEntityMetaData($this->getEntityTypeId())->getEntityTable();

        // Define store related conditions, keep the order of the array elements!
        $storeConditions = [
            'default' => $this->connection->quoteInto('t_attribute.store_id = ?', Store::DEFAULT_STORE_ID),
            'store'   => $this->connection->quoteInto('t_attribute.store_id = ?', $storeId),
        ];

        $result = [];
        foreach ($storeConditions as $condition) {
            $joinAttributeValuesCondition = [
                new \Zend_Db_Expr("entity.$linkField = t_attribute.$linkField"),
                $condition,
            ];

            $joinAttributeValuesCondition = implode(' AND ', $joinAttributeValuesCondition);

            $select = $this->connection->select();
            $select->from(['entity' => $entityTable], [$entityIdField])
                ->joinLeft(
                    ['t_attribute' => $tableName],
                    $joinAttributeValuesCondition,
                    ['attribute_id', 'value']
                )
                ->joinInner(
                    ['attr' => $this->getTable('eav_attribute')],
                    "t_attribute.attribute_id = attr.attribute_id",
                    ['attribute_id', 'attribute_code']
                )
                ->where("entity.{$entityIdField} IN (?)", $entityIds)
                ->where("t_attribute.attribute_id IN (?)", $attributeIds)
                ->where("t_attribute.value IS NOT NULL");

            // Get the result and override values from a previous loop.
            foreach ($this->connection->fetchAll($select) as $row) {
                $key = "{$row['entity_id']}-{$row['attribute_id']}";
                $result[$key] = $row;
            }
        }

        return array_values($result);
    }

    /**
     * Get Entity Type Id.
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        return $this->entityTypeId;
    }
}
