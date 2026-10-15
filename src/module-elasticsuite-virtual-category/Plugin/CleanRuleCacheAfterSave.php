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
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory\CollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;

/**
 * Clean the virtual rules cache when a category is saved, deleted or moved.
 *
 * Only the cache entries depending on the category or on one of its ancestors are cleaned:
 * any category query embeds the queries of its virtual descendants.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class CleanRuleCacheAfterSave
{
    /**
     * @var CacheCleaner
     */
    private $cacheCleaner;

    /**
     * Constructor.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param CacheInterface    $cache                     Deprecated, unused: the cache is cleaned by the cache cleaner.
     * @param CollectionFactory $categoryCollectionFactory Deprecated, unused: the cache entries are tagged with
     *                                                     the categories they depend on.
     * @param CacheCleaner|null $cacheCleaner              Virtual rules cache cleaner.
     */
    public function __construct(
        CacheInterface $cache,
        CollectionFactory $categoryCollectionFactory,
        ?CacheCleaner $cacheCleaner = null
    ) {
        $this->cacheCleaner = $cacheCleaner ?: ObjectManager::getInstance()->get(CacheCleaner::class);
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
            $this->cacheCleaner->cleanAfterCommit($category->getPathIds());
        }

        return $resourceCategory;
    }

    /**
     * Clean category search rule cache on category deletion.
     * The deleted category descendants, deleted along with it, are cleaned too.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ResourceCategory $subject  Category resource model.
     * @param callable         $proceed  Original method.
     * @param mixed            $category Deleted category.
     * @return ResourceCategory
     */
    public function aroundDelete(ResourceCategory $subject, callable $proceed, $category)
    {
        // Descendants must be retrieved before they are deleted.
        $categoryIds = [];
        if ($category instanceof Category) {
            $categoryIds = array_merge($category->getPathIds(), $category->getAllChildren(true));
        }

        $result = $proceed($category);

        $this->cacheCleaner->cleanAfterCommit($categoryIds);

        return $result;
    }

    /**
     * Clean category search rule cache on category move.
     * Both the former and the new ancestors are cleaned, as well as the moved category descendants.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ResourceCategory $subject         Category resource model.
     * @param callable         $proceed         Original method.
     * @param Category         $category        Moved category.
     * @param Category         $newParent       New parent category.
     * @param int|null         $afterCategoryId Category after which the moved category is positioned.
     * @return ResourceCategory
     */
    public function aroundChangeParent(
        ResourceCategory $subject,
        callable $proceed,
        Category $category,
        Category $newParent,
        $afterCategoryId = null
    ) {
        // Former ancestors and descendants must be retrieved before the category paths are updated.
        $categoryIds = array_merge($category->getPathIds(), $category->getAllChildren(true));

        $result = $proceed($category, $newParent, $afterCategoryId);

        $this->cacheCleaner->cleanAfterCommit(array_merge($categoryIds, $newParent->getPathIds()));

        return $result;
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
            'is_active' => $category->getOrigData('is_active'),
            'is_virtual_category' => $category->getOrigData('is_virtual_category'),
            'virtual_rule' => "{$category->getOrigData('virtual_rule')}",
            'virtual_category_root' => $category->getOrigData('virtual_category_root'),
        ];
        $newData = [
            'is_active' => $category->getData('is_active'),
            'is_virtual_category' => $category->getData('is_virtual_category'),
            'virtual_rule' => "{$category->getData('virtual_rule')}",
            'virtual_category_root' => $category->getData('virtual_category_root'),
        ];

        return !empty(array_diff_assoc($originalData, $newData));
    }
}
