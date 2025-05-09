<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as ResourceCategory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;

/**
 * Clean rule cache on category save.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class CleanRuleCacheAfterSave
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Constructor.
     *
     * @param CacheInterface    $cache                     Cache.
     * @param CollectionFactory $categoryCollectionFactory Category collection factory.
     */
    public function __construct(
        CacheInterface $cache,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->cache = $cache;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Clean category search rule cache on category save.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ResourceCategory $subject          Category resource model.
     * @param ResourceCategory $resourceCategory Category resource model return by original method.
     * @param AbstractModel    $category         Saved category.
     * @return ResourceCategory
     */
    public function afterSave(ResourceCategory $subject, ResourceCategory $resourceCategory, AbstractModel $category)
    {
        if ($this->hasVirtualDataChange($category)) {
            $tagsToClean = $this->getAffectedCategories([$category]);
            $this->cache->clean($tagsToClean);
        }

        return $resourceCategory;
    }

    /**
     * Check if saved category has some update in its virtual category data.
     *
     * @param AbstractModel $category Category.
     * @return bool
     */
    private function hasVirtualDataChange(AbstractModel $category): bool
    {
        $originalData = [
            'is_virtual_category' => $category->getOrigData('is_virtual_category'),
            'virtual_rule' => "{$category->getOrigData('virtual_rule')}",
            'virtual_category_root' => $category->getOrigData('virtual_category_root'),
        ];
        $newData = [
            'is_virtual_category' => $category->getData('is_virtual_category'),
            'virtual_rule' => "{$category->getData('virtual_rule')}",
            'virtual_category_root' => $category->getData('virtual_category_root'),
        ];

        return !empty(array_diff($originalData, $newData));
    }

    /**
     * Get all category ids affected by the given category rules.
     *
     * @param array $categories Category list to check.
     * @param array $tagList    Current calculated tag list to clean.
     * @return array
     * @throws LocalizedException
     */
    private function getAffectedCategories(array $categories, array $tagList = [])
    {
        $parentCategoryIds = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            // We need the root and the category of level 1 as they are the root of the website.
            $parentCategoryIds = array_merge($parentCategoryIds, array_slice($category->getPathIds(), 2));
        }

        // Add the parent category in the list of the category rule to flush.
        foreach ($parentCategoryIds as $categoryId) {
            $tagList[$categoryId] = Category::CACHE_TAG . '_' . $categoryId;
        }

        // Search for virtual category that use the cleaned category as root category.
        $affectedCategories = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('level', ['gt' => 1])
            ->addAttributeToFilter('is_virtual_category', ['eq' => true])
            ->addAttributeToFilter('virtual_category_root', ['in' => $parentCategoryIds])
            ->addAttributeToFilter('entity_id', ['nin' => array_keys($tagList)]);

        foreach ($affectedCategories as $category) {
            $tagList[$category->getId()] = Category::CACHE_TAG . '_' . $category->getId();
        }

        // If cleaned categories are used as virtual category root, re-run this algo with these categories.
        if ($affectedCategories->count()) {
            $tagList = $this->getAffectedCategories($affectedCategories->getItems(), $tagList);
        }

        return $tagList;
    }
}
