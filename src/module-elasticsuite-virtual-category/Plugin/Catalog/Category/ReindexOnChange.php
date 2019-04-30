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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

/**
 * Trigger reindexing for category when switched from Standard to "Virtual Category"
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReindexOnChange
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * ReindexOnChange constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry Indexer Registry
     */
    public function __construct(\Magento\Framework\Indexer\IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Enforce reindexing of path ids if the category has just been set to 'is_virtual_category' = true.
     *
     * @param \Magento\Catalog\Model\Category $category The category
     * @param \Closure                        $proceed  The Category::reindex() method
     */
    public function aroundReindex(\Magento\Catalog\Model\Category $category, \Closure $proceed)
    {
        $proceed();

        // Reindex only if attached product list has changed.
        // This prevent reindexing if the category is just created and set to virtual.
        if ($category->dataHasChangedFor('is_virtual_category') && ($category->getIsChangedProductList() === true)) {
            if (((bool) $category->getIsVirtualCategory() === true) && ($category->getId())) {
                if (!$this->getIndexer()->isScheduled()) {
                    // Remove default root category (1) and Store Root.
                    $this->getIndexer()->reindexList(array_slice($category->getPathIds(), 2));
                }
            }
        }
    }

    /**
     * Retrieve Category/Product indexer.
     *
     * @return \Magento\Framework\Indexer\IndexerInterface
     */
    private function getIndexer()
    {
        return $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Category\Product::INDEXER_ID);
    }
}
