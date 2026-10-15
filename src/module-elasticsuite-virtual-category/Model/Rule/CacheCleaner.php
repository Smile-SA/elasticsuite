<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Cache\Type\Elasticsuite as ElasticsuiteCache;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;

/**
 * Cleans the virtual rules cache entries of some categories, and of the virtual categories using one of them as
 * virtual root, recursively: their queries embed the query of their root.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CacheCleaner
{
    /**
     * @var ElasticsuiteCache
     */
    private $cache;

    /**
     * @var QueryCache
     */
    private $queryCache;

    /**
     * @var CategoryResource
     */
    private $categoryResource;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var RootCategoryProvider
     */
    private $rootCategoryProvider;

    /**
     * Constructor.
     *
     * @param ElasticsuiteCache    $cache                     Elasticsuite cache.
     * @param QueryCache           $queryCache                Virtual rules query cache.
     * @param CategoryResource     $categoryResource          Category resource model.
     * @param CollectionFactory    $categoryCollectionFactory Virtual categories collection factory.
     * @param RootCategoryProvider $rootCategoryProvider      Virtual root categories provider.
     */
    public function __construct(
        ElasticsuiteCache $cache,
        QueryCache $queryCache,
        CategoryResource $categoryResource,
        CollectionFactory $categoryCollectionFactory,
        RootCategoryProvider $rootCategoryProvider
    ) {
        $this->cache                     = $cache;
        $this->queryCache                = $queryCache;
        $this->categoryResource          = $categoryResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->rootCategoryProvider      = $rootCategoryProvider;
    }

    /**
     * Clean the cache entries of the given categories, and of the virtual categories using one of them as root.
     *
     * @param int[] $categoryIds Category ids, which must include the changed categories ancestors.
     *
     * @return void
     */
    public function clean(array $categoryIds): void
    {
        $cacheTags = array_map(
            function ($categoryId) {
                return Category::CACHE_TAG . '_' . $categoryId;
            },
            $this->addVirtualRootUsers(array_map('intval', $categoryIds))
        );

        if (!empty($cacheTags)) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $cacheTags);
            // Queries and root categories kept by the current process may be outdated as well.
            $this->queryCache->reset();
            $this->rootCategoryProvider->reset();
        }
    }

    /**
     * Clean the cache entries of the given categories or of any of their ancestors.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return void
     */
    public function cleanWithAncestors(array $categoryIds): void
    {
        $this->clean($this->getPathIds($categoryIds));
    }

    /**
     * Ids of the given categories and of all their ancestors.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return int[]
     */
    private function getPathIds(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $connection = $this->categoryResource->getConnection();
        $select     = $connection->select()
            ->distinct()
            ->from($this->categoryResource->getEntityTable(), ['path'])
            ->where('entity_id IN (?)', array_map('intval', $categoryIds));

        $pathIds = [];
        foreach ($connection->fetchCol($select) as $path) {
            $pathIds = array_merge($pathIds, explode('/', (string) $path));
        }

        return array_values(array_unique(array_map('intval', $pathIds)));
    }

    /**
     * Add to the given categories the virtual categories using one of them as virtual root, with their ancestors,
     * and so on with the categories added.
     * For example, with V (path 1/2/30/31) using 12 as root, cleaning [1, 2, 3, 12] also cleans 30 and 31.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return int[]
     */
    private function addVirtualRootUsers(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique($categoryIds));
        $rootIds     = $categoryIds;

        while (!empty($rootIds)) {
            $collection = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('level', ['gt' => 1])
                ->addAttributeToFilter('is_virtual_category', ['eq' => true])
                ->addAttributeToFilter('virtual_category_root', ['in' => $rootIds])
                ->addAttributeToFilter('entity_id', ['nin' => $categoryIds]);

            $rootIds = [];
            foreach ($collection as $category) {
                $newIds      = array_diff(array_map('intval', $category->getPathIds()), $categoryIds);
                $categoryIds = array_merge($categoryIds, $newIds);
                $rootIds     = array_merge($rootIds, $newIds);
            }
        }

        return $categoryIds;
    }
}
