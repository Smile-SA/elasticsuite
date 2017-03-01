<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Indexer\Category\Product;

use Magento\Catalog\Api\CategoryAttributeRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Set Categories to is_anchor=1 before reindexing category/products associations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SetIsAnchorBeforeIndexing
{
    /**
     * @var \Magento\Catalog\Api\CategoryAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * SetIsAnchorBeforeIndexing constructor.
     *
     * @param \Magento\Catalog\Api\CategoryAttributeRepositoryInterface $attributeRepositoryInterface Category Attributes Repository
     * @param \Magento\Framework\EntityManager\MetadataPool             $metadataPool                 Metadata Pool
     * @param \Magento\Framework\App\ResourceConnection                 $resource                     Resource Connection
     */
    public function __construct(
        CategoryAttributeRepositoryInterface $attributeRepositoryInterface,
        MetadataPool $metadataPool,
        ResourceConnection $resource
    ) {
        $this->attributeRepository = $attributeRepositoryInterface;
        $this->metadataPool        = $metadataPool;
        $this->connection          = $resource->getConnection();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Set categories to is_anchor=true before indexing
     *
     * @param \Magento\Catalog\Model\Indexer\Category\Product $subject The Catalog/Product indexer
     * @param \Closure                                        $proceed The ::execute() function of $subject
     *
     * @return void
     */
    public function aroundExecuteFull(
        \Magento\Catalog\Model\Indexer\Category\Product $subject,
        \Closure $proceed
    ) {
        $this->updateIsAnchorAttribute();
        $proceed();

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Set categories to is_anchor=true before indexing
     *
     * @param \Magento\Catalog\Model\Indexer\Category\Product $subject     The Catalog/Product indexer
     * @param \Closure                                        $proceed     The ::execute() function of $subject
     * @param int[]                                           $categoryIds The category ids being reindexed
     *
     * @return void
     */
    public function aroundExecute(
        \Magento\Catalog\Model\Indexer\Category\Product $subject,
        \Closure $proceed,
        $categoryIds
    ) {
        $this->updateIsAnchorAttribute($categoryIds);
        $proceed($categoryIds);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Set categories to is_anchor=true before indexing
     *
     * @param \Magento\Catalog\Model\Indexer\Category\Product $subject    The Catalog/Product indexer
     * @param \Closure                                        $proceed    The ::executeRow() function of $subject
     * @param int                                             $categoryId The category id being reindexed
     *
     * @return void
     */
    public function aroundExecuteRow(
        \Magento\Catalog\Model\Indexer\Category\Product $subject,
        \Closure $proceed,
        $categoryId
    ) {
        $this->updateIsAnchorAttribute([$categoryId]);
        $proceed($categoryId);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Set categories to is_anchor=true before indexing
     *
     * @param \Magento\Catalog\Model\Indexer\Category\Product $subject     The Catalog/Product indexer
     * @param \Closure                                        $proceed     The ::execute() function of $subject
     * @param int[]                                           $categoryIds The category ids being reindexed
     *
     * @return void
     */
    public function aroundExecuteList(
        \Magento\Catalog\Model\Indexer\Category\Product $subject,
        \Closure $proceed,
        array $categoryIds
    ) {

        $this->updateIsAnchorAttribute($categoryIds);
        $proceed($categoryIds);

        return;
    }

    /**
     * Automatically set the categories being indexed as is_anchor = 1
     *
     * @param array $categoryIds The category Ids
     *
     * @throws \Exception
     */
    private function updateIsAnchorAttribute($categoryIds = [])
    {
        $attribute      = $this->attributeRepository->get('is_anchor');
        $metaData       = $this->metadataPool->getMetadata(CategoryInterface::class);
        $entityTable    = $metaData->getEntityTable();
        $attributeId    = (int) $attribute->getAttributeId();
        $attributeTable = $attribute->getBackendTable();
        $linkField      = $metaData->getLinkField();

        $entitySelect = $this->connection->select()->from(
            ['cat' => $entityTable],
            [new \Zend_Db_Expr("{$attributeId} as attribute_id"), $linkField, new \Zend_Db_Expr("1 as value")]
        );

        if (!empty($categoryIds)) {
            $entitySelect->where("cat.entity_id IN(?)", array_map('intval', $categoryIds));
        }
        $entitySelect->where("cat.entity_id NOT IN(?)", [\Magento\Catalog\Model\Category::TREE_ROOT_ID]);

        $joinConditions = ["cat.{$linkField} = attr.{$linkField}", "attr.attribute_id = {$attributeId}"];
        $entitySelect->joinLeft(['attr' => $attributeTable], new \Zend_Db_Expr(implode(" AND ", $joinConditions)), []);
        $entitySelect->where(new \Zend_Db_Expr('COALESCE(attr.value, 0) <> 1'));

        $insertQuery = $this->connection->insertFromSelect(
            $entitySelect,
            $attributeTable,
            ['attribute_id', $linkField, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $this->connection->query($insertQuery);
    }
}
