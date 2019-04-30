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
namespace Smile\ElasticsuiteVirtualCategory\Plugin\CatalogSearch\Indexer;

/**
 * Plugin that will cleanup virtual categories cache after a full reindex.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FulltextPlugin
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Indexer\CacheContext
     */
    private $cacheContext;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * FulltextPlugin constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface                       $eventManager              Event Manager
     * @param \Magento\Framework\Indexer\CacheContext                         $cacheContext              Cache Context
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory Category Collection Factory
     *
     * @internal param \Magento\Framework\App\Cache\Type\FrontendPool $frontendCachePool Frontend Cache Pool
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Indexer\CacheContext $cacheContext,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    ) {
        $this->eventManager              = $eventManager;
        $this->cacheContext              = $cacheContext;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * After a full reindex of catalogsearch_fulltext index :
     *  - cleanup the cache tag of each virtual category and their parents.
     *
     * @param \Magento\CatalogSearch\Model\Indexer\Fulltext $subject Catalog product fulltext indexer
     * @param void                                          $result  Void result
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteFull(\Magento\CatalogSearch\Model\Indexer\Fulltext $subject, $result)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
        $categories = $this->categoryCollectionFactory->create();

        // Can occur during setup:install.
        if (false === $categories->getEntity()->getAttribute('is_virtual_category')) {
            return;
        }

        $categories->addAttributeToSelect(['is_virtual_category'])
            ->addIsActiveFilter()
            ->addAttributeToFilter('is_virtual_category', 1);

        $categoryIds = [];

        // Foreach virtual Category, compute list of it's ancestors to purge their cache.
        foreach ($categories as $category) {
            $categoryIds = array_merge(
                $categoryIds,
                [$category->getId()],
                array_slice($category->getPathIds(), 2) // Exclude default root (1) and Store Root.
            );
        }

        $categoryIds = array_unique($categoryIds);

        $this->cleanCategoryCacheByIds($categoryIds);
    }

    /**
     * Clean cache of frontend pages based on category ids.
     *
     * @param array $categoryIds Category Ids
     */
    private function cleanCategoryCacheByIds(array $categoryIds)
    {
        $this->cacheContext->registerEntities(\Magento\Catalog\Model\Category::CACHE_TAG, $categoryIds);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
