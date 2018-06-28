<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Indexer;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full as FullIndexer;

/**
 * Generic indexer plugin, handling fulltext index process
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractIndexerPlugin
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full
     */
    private $fullIndexer;

    /**
     * ReindexProductsAfterSave constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry The indexer registry
     * @param FullIndexer                                $fullIndexer     The Full Indexer
     */
    public function __construct(\Magento\Framework\Indexer\IndexerRegistry $indexerRegistry, FullIndexer $fullIndexer)
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->fullIndexer     = $fullIndexer;
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

        $parentIds  = $this->fullIndexer->getRelationsByChild($ids);
        $processIds = $parentIds ? array_merge($parentIds, $ids) : $ids;

        if (!$fullTextIndexer->isScheduled()) {
            if (!empty($processIds)) {
                $fullTextIndexer->reindexList($processIds);
            }
        }
    }
}
