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
namespace Smile\ElasticSuiteCatalog\Plugin\Indexer\Category\Save;

/**
 * Plugin that proceed category reindex in ES after category reindexing
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReindexCategoryAfterSave
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * ReindexCategoryAfterSave constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry The indexer registry
     */
    public function __construct(\Magento\Framework\Indexer\IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex category's data after into search engine after reindexing the category
     *
     * @param \Magento\Catalog\Model\Category $subject The category being reindexed
     * @param callable                        $proceed The parent function we are plugged on
     *                                                 : Magento\Catalog\Model\Category::reindex()
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function aroundReindex(
        \Magento\Catalog\Model\Category $subject,
        callable $proceed
    ) {
        $proceed();

        if ($subject->getLevel() > 1) {
            $categoryIndexer = $this->indexerRegistry->get(\Smile\ElasticSuiteCatalog\Model\Category\Indexer\Fulltext::INDEXER_ID);
            $categoryIndexer->reindexRow($subject->getId());
        }

        return;
    }
}
