<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Build a custom sort algorithm based on a list of SKUs.
 * Used to mimic the "sku_position" legacy sort that is used by pagebuilder widgets.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Builder
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * AbstractAttributeData constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection     $resource     Resource Connection
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool Entity Metadata Pool
     */
    public function __construct(
        ResourceConnection $resource,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Build a custom sort algorithm with scripting to enforce products positions.
     *
     * @param array $skus An array of Product SKUs that should keep their order preserved.
     *
     * @return array
     */
    public function buildSortOrder($skus)
    {
        // An array where entity_id is the key, and position is the value.
        $productPositions = $this->getProductIds($skus);

        // @codingStandardsIgnoreStart
        $scriptSource = "if(params.scores.containsKey(doc['_id'].value)) { return params.scores[doc['_id'].value];} return 922337203685477600L";
        // @codingStandardsIgnoreEnd

        return [
            '_script' => [
                'lang'       => 'painless',
                'scriptType' => 'number',
                'source'     => $scriptSource,
                'params'     => [
                    'scores' => array_map('intval', $productPositions),
                ],
                'direction'  => 'asc',
            ],
        ];
    }

    /**
     * Fetch a pair containing entity_id/sku while keeping the order.
     *
     * @param array $skus a list of SKUs
     *
     * @return array
     */
    private function getProductIds($skus)
    {
        $productMetadata = $this->getEntityMetaData(ProductInterface::class);

        // The legacy entity_id field.
        $entityIdField = $productMetadata->getIdentifierField();

        $select = $this->resource->getConnection()
                      ->select()
                      ->from(['e' => $productMetadata->getEntityTable()], [$entityIdField])
                      ->where('e.sku IN (?)', ['in' => $skus]);

        $orderList = "'" . join("','", array_filter($skus)) . "'";
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->order(new \Zend_Db_Expr("FIELD(e.sku,$orderList)"));

        $productIds = $this->resource->getConnection()->fetchCol($select);
        $products   = [];
        $position   = 1;

        foreach ($productIds as $productId) {
            $products[(int) $productId] = $position;
            $position ++;
        }

        return $products;
    }

    /**
     * Retrieve Metadata for an entity
     *
     * @param string $entityType The entity
     *
     * @return EntityMetadataInterface
     */
    private function getEntityMetaData($entityType)
    {
        return $this->metadataPool->getMetadata($entityType);
    }
}
