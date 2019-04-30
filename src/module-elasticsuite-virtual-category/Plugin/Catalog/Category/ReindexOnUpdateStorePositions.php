<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

use Magento\Framework\Indexer\IndexerRegistry;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\Indexer\Fulltext\Changelog\Writer as ChangelogWriter;

/**
 * Class ReindexOnUpdateStorePositions
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class ReindexOnUpdateStorePositions
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ChangelogWriter
     */
    private $changeLogWriter;

    /**
     * ReindexOnUpdateStorePositions constructor.
     *
     * @param IndexerRegistry $indexerRegistry Indexer registry
     * @param ChangelogWriter $changeLogWriter Resource
     */
    public function __construct(IndexerRegistry $indexerRegistry, ChangelogWriter $changeLogWriter)
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->changeLogWriter = $changeLogWriter;
    }

    /**
     * Forcibly adds the category's affected products to the fulltext indexer changelog if the indexer is scheduled,
     * and if the 'use_store_positions' attribute value has been changed knowingly (or if this is a possibility).
     * This is to cover the case where the user decides to have no product positioned or blacklisted at the store level,
     * from a configuration where the category had positioned and blacklisted products at the default level.
     * As no record will be altered in the products position table, the indexer view triggers will not write anything
     * on their own in the changelog table.
     *
     * @param \Magento\Catalog\Model\Category $category The category
     * @param \Closure                        $proceed  The Category::reindex() method
     *
     * @return void
     */
    public function aroundReindex(\Magento\Catalog\Model\Category $category, \Closure $proceed)
    {
        $proceed();

        if (!empty($category->getAffectedProductIds())
            && ($category->dataHasChangedFor('use_store_positions') || (false == $category->getUseStorePositions()))
        ) {
            $fullTextIndexer = $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);
            if ($fullTextIndexer->isScheduled()) {
                $this->changeLogWriter->addProducts($category->getAffectedProductIds());
            }
        }
    }
}
