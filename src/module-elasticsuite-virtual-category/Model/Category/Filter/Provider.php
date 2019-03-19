<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Category\Filter;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface;

/**
 * Category filter provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Provider extends \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler
     */
    private $readHandler;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * Provider constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory                           $queryFactory Query Factory
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler $readHandler  Read Handler
     * @param \Magento\Framework\App\CacheInterface                                               $cache        Cache
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler $readHandler,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        parent::__construct($queryFactory);
        $this->readHandler = $readHandler;
        $this->cache       = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionValue(CategoryInterface $category)
    {
        return $this->getCategorySearchQuery($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryFilter(CategoryInterface $category)
    {
        return $this->getCategorySearchQuery($category);
    }

    /**
     * Get category search query
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getCategorySearchQuery(CategoryInterface $category)
    {
        $virtualRule = $category->getVirtualRule();
        if (!($virtualRule instanceof VirtualRuleInterface)) {
            return $this->loadVirtualRule($category)->getCategorySearchQuery($category);
        }

        return $this->loadUsingCache($category, 'getCategorySearchQuery');
    }

    /**
     * Load virtual rule of a category. Can occurs when data is set directly as array to the category
     * (Eg. when the category edit form is submitted with error and populated from session data).
     *
     * @param CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    private function loadVirtualRule(CategoryInterface $category)
    {
        $this->readHandler->execute($category);

        return $category->getVirtualRule();
    }

    /**
     * Load data from the cache if exist. Use a callback on the current category if not yet present into the cache.
     *
     * @param CategoryInterface $category Category
     * @param string            $callback Name of the virtual rule method to be used for actual loading.
     *
     * @return mixed
     */
    private function loadUsingCache(CategoryInterface $category, $callback)
    {
        $cacheKey = implode('|', [$callback, $category->getStoreId(), $category->getId()]);

        $data = $this->cache->load($cacheKey);

        if ($data !== false) {
            $data = unserialize($data);
        }

        if ($data === false) {
            $virtualRule = $category->getVirtualRule();
            $data = call_user_func_array([$virtualRule, $callback], [$category]);
            $cacheData = serialize($data);
            $this->cache->save($cacheData, $cacheKey, [\Magento\Catalog\Model\Category::CACHE_TAG]);
        }

        return $data;
    }
}
