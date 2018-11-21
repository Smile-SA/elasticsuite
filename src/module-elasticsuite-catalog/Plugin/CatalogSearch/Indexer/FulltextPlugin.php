<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\CatalogSearch\Indexer;

/**
 * Plugin that will cleanup popular searches cache after a full reindex.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FulltextPlugin
{
    /**
     * @var \Magento\Framework\App\Cache\Type\FrontendPool
     */
    private $frontendCachePool;

    /**
     * FulltextPlugin constructor.
     *
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $frontendCachePool Frontend Cache Pool
     */
    public function __construct(\Magento\Framework\App\Cache\Type\FrontendPool $frontendCachePool)
    {
        $this->frontendCachePool = $frontendCachePool;
    }

    /**
     * After a full reindex of catalogsearch_fulltext index :
     *  - cleanup the cache for items matching the popular search results tag.
     *
     * @param \Magento\CatalogSearch\Model\Indexer\Fulltext $subject Catalog product fulltext indexer
     * @param void                                          $result  Void result
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteFull(\Magento\CatalogSearch\Model\Indexer\Fulltext $subject, $result)
    {
        try {
            $this->frontendCachePool->get(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER)->clean(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                [\Smile\ElasticsuiteCatalog\Block\CatalogSearch\Result\Cache::POPULAR_SEARCH_CACHE_TAG]
            );
        } catch (\InvalidArgumentException $exception) {
            ;
        }
    }
}
