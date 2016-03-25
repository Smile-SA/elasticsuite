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
     */
    public function __construct(\Magento\Framework\Indexer\IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Process full-text reindex for product ids
     *
     * @param mixed $ids The product ids to reindex
     */
    protected function processFullTextIndex($ids)
    {
        $fullTextIndexer = $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);

        if (!$fullTextIndexer->isScheduled()) {
            if (is_array($ids) && (!empty($ids))) {
                $fullTextIndexer->reindexList($ids);
            }
            if (!is_array($ids) && is_numeric($ids)) {
                $fullTextIndexer->reindexRow($ids);
            }
        }
    }
}
