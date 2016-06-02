<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Plugin\Indexer;

use Magento\Framework\App\ResourceConnection;

/**
 * Generic indexer plugin, handling fulltext index process
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractIndexerPlugin
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * ReindexProductsAfterSave constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry The indexer registry
     * @param ResourceConnection                         $resource        The Resource connection
     */
    public function __construct(\Magento\Framework\Indexer\IndexerRegistry $indexerRegistry, ResourceConnection $resource)
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->resource        = $resource;
    }

    /**
     * Process full-text reindex for product ids
     *
     * @param mixed $ids The product ids to reindex
     */
    protected function processFullTextIndex($ids)
    {
        $fullTextIndexer = $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $parentIds  = $this->getRelationsByChild($ids);
        $processIds = $parentIds ? array_merge($parentIds, $ids) : $ids;

        if (!$fullTextIndexer->isScheduled()) {
            if (!empty($processIds)) {
                $fullTextIndexer->reindexList($processIds);
            }
        }
    }

    /**
     * Retrieve products relations by childrens
     *
     * @param array $childrenIds The product ids being reindexed
     *
     * @return array
     */
    private function getRelationsByChild($childrenIds)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_product_relation'), 'parent_id')
            ->where('child_id IN(?)', $childrenIds);

        return $connection->fetchCol($select);
    }
}
