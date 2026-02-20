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

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Type as ProductType;

/**
 * Attributes datasource resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeData extends AbstractAttributeData
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
     * Eav config
     *
     * @var Config
     */
    private $eavConfig;

    /**
     * Scope Config
     *
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor.
     *
     * @param ResourceConnection    $resource           Database adapter.
     * @param StoreManagerInterface $storeManager       Store manager.
     * @param MetadataPool          $metadataPool       Metadata Pool.
     * @param ProductType           $catalogProductType Product type.
     * @param Config                $eavConfig          Eav config.
     * @param ScopeConfigInterface  $scopeConfig        Scope Config.
     * @param string                $entityType         Product entity type.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        ProductType $catalogProductType,
        Config $eavConfig,
        ScopeConfigInterface $scopeConfig,
        $entityType = ProductInterface::class
    ) {
        parent::__construct($resource, $storeManager, $metadataPool, $entityType);
        $this->catalogProductType = $catalogProductType;
        $this->eavConfig = $eavConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * List of composite product types.
     *
     * @return string[]
     */
    public function getCompositeTypes()
    {
        return $this->catalogProductType->getCompositeTypes();
    }

    /**
     * Retrieve list of children ids for a product list.
     *
     * Warning the result use children ids as a key and list of parents as value
     *
     * @param array $productIds List of parent product ids.
     * @param int   $storeId    Store id.
     *
     * @return array
     */
    public function loadChildrens($productIds, $storeId)
    {
        $children = [];

        foreach ($this->catalogProductType->getCompositeTypes() as $productTypeId) {
            $typeInstance = $this->getProductTypeInstance($productTypeId);
            $relation = $typeInstance->getRelationInfo();

            if ($relation->getTable() && $relation->getParentFieldName() && $relation->getChildFieldName()) {
                $select = $this->getRelationQuery($relation, $productIds, $storeId);
                $data   = $this->getConnection()->fetchAll($select);

                foreach ($data as $relationRow) {
                    $parentId = (int) $relationRow['parent_id'];
                    $childId  = (int) $relationRow['child_id'];
                    $sku      = (string) $relationRow['sku'];
                    $configurableAttributes = array_filter(explode(',', $relationRow["configurable_attributes"] ?? ''));
                    $children[$childId][] = [
                        "parent_id"               => $parentId,
                        "configurable_attributes" => $configurableAttributes,
                        "sku"                     => $sku,
                    ];
                }
            }
        }

        return $children;
    }

    /**
     * Allow to filter an attribute collection on attributes that are indexed into the search engine.
     *
     * Inherited to enforce "status" and "sku" attribute indexing for products.
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection Attribute Collection
     *
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    public function addIndexedFilterToAttributeCollection(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection
    ) {
        $attributeCollection = parent::addIndexedFilterToAttributeCollection($attributeCollection);
        $entityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();

        $attributeCollection->getSelect()->orWhere(
            "attribute_code IN ('status', 'sku') AND main_table.entity_type_id=?",
            $entityTypeId
        );

        return $attributeCollection;
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

    /**
     * Get Entity Id used by this indexer
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        return ProductInterface::class;
    }

    /**
     * Retrieve
     *
     * @param \Magento\Framework\DataObject $relation  Relation Instance
     * @param array                         $parentIds The parent product Ids (array of entity_id)
     * @param int                           $storeId   Store id.
     *
     * @return \Magento\Framework\DB\Select
     */
    private function getRelationQuery($relation, $parentIds, $storeId)
    {
        $linkField       = $this->getEntityMetaData($this->getEntityTypeId())->getLinkField();
        $entityIdField   = $this->getEntityMetaData($this->getEntityTypeId())->getIdentifierField();
        $entityTable     = $this->getTable($this->getEntityMetaData($this->getEntityTypeId())->getEntityTable());
        $relationTable   = $this->getTable($relation->getTable());
        $inventoryTable  = $this->getTable('cataloginventory_stock_item');
        $parentFieldName = $relation->getParentFieldName();
        $childFieldName  = $relation->getChildFieldName();

        $select = $this->getConnection()->select()
            ->from(['main' => $relationTable], [])
            ->joinInner(
                ['parent' => $entityTable],
                new \Zend_Db_Expr("parent.{$linkField} = main.{$parentFieldName}"),
                ['parent_id' => $entityIdField]
            )
            ->joinInner(
                ['child' => $entityTable],
                new \Zend_Db_Expr("child.{$entityIdField} = main.{$childFieldName}"),
                ['child_id' => $entityIdField, 'sku' => 'sku']
            )
            ->where("parent.{$entityIdField} in (?)", $parentIds);

        /**
         * If Catalog - Inventory - Stock Options - Display of Stock Products is set to NO,
         * then exclude this children from the query results.
         */
        if (!$this->scopeConfig->getValue(Configuration::XML_PATH_SHOW_OUT_OF_STOCK)) {
            $select->joinInner(
                ['stock' => $inventoryTable],
                new \Zend_Db_Expr("child.{$entityIdField} = stock.product_id AND stock.is_in_stock = 1"),
                []
            );
        }

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

        $select->group(["main.{$parentFieldName}", "main.{$childFieldName}"]);

        return $this->addWebsiteFilter($select, "main", $childFieldName, $storeId);
    }

    /**
     * Add website clauses to products selected.
     *
     * @param \Magento\Framework\DB\Select $select           Original select.
     * @param string                       $productTableName Product table name in the original select.
     * @param string                       $productFieldName Product id field name in the original select.
     * @param int                          $storeId          Store id.
     *
     * @return \Magento\Framework\DB\Select $select
     */
    private function addWebsiteFilter(\Magento\Framework\DB\Select $select, $productTableName, $productFieldName, $storeId)
    {
        $websiteId  = $this->getStore($storeId)->getWebsiteId();
        $indexTable = $this->getTable('catalog_product_website');

        $visibilityJoinCond = $this->getConnection()->quoteInto(
            "websites.product_id = $productTableName.$productFieldName AND websites.website_id = ?",
            $websiteId
        );

        $select->useStraightJoin(true)->join(['websites' => $indexTable], $visibilityJoinCond, []);

        return $select;
    }
}
