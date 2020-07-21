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
        $select = $this->connection->select();

        // The field modelizing the link between entity table and attribute values table. Either row_id or entity_id.
        $linkField = $this->getEntityMetaData($this->getEntityTypeId())->getLinkField();

        // The legacy entity_id field.
        $entityIdField = $this->getEntityMetaData($this->getEntityTypeId())->getIdentifierField();

        $joinDefaultValuesCondition = [
            new \Zend_Db_Expr("entity.$linkField = t_default.$linkField"),
            't_default.attribute_id = attr.attribute_id',
            $this->connection->quoteInto('t_default.store_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID),
        ];
        $joinDefaultValuesCondition = implode(' AND ', $joinDefaultValuesCondition);

        $joinStoreValuesConditionClauses = [
            new \Zend_Db_Expr("entity.$linkField = t_store.$linkField"),
            't_store.attribute_id = attr.attribute_id',
            $this->connection->quoteInto('t_store.store_id = ?', $storeId),
        ];
        $joinStoreValuesCondition = implode(' AND ', $joinStoreValuesConditionClauses);

        $select->from(['entity' => $this->getEntityMetaData($this->getEntityTypeId())->getEntityTable()], [$entityIdField])
            ->joinInner(
                ['attr' => $this->getTable('eav_attribute')],
                $this->connection->quoteInto('attr.attribute_id IN (?)', $attributeIds),
                ['attribute_id']
            )
            ->joinLeft(
                ['t_default' => $tableName],
                $joinDefaultValuesCondition,
                []
            )
            ->joinLeft(
                ['t_store' => $tableName],
                $joinStoreValuesCondition,
                []
            )
            ->where("entity.{$entityIdField} IN (?)", $entityIds)
            ->having('value IS NOT NULL')
            ->columns(['value' => new \Zend_Db_Expr('COALESCE(t_store.value, t_default.value)')]);

        return $this->connection->fetchAll($select);
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
