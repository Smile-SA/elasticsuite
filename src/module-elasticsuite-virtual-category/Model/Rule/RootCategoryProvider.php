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

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Loads the categories used by many virtual rules (virtual roots, store root categories, expansion boundaries),
 * once per process for all the virtual rules using them.
 *
 * A category used as virtual root by several virtual categories (eg. a category and its subset subcategories) would
 * otherwise be loaded once per virtual rule, since each virtual category has its own rule instance.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RootCategoryProvider
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var CategoryInterface[][]
     */
    private $categories = [];

    /**
     * Constructor.
     *
     * @param CategoryFactory $categoryFactory Category factory.
     */
    public function __construct(CategoryFactory $categoryFactory)
    {
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Get a virtual root category.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param int      $categoryId Category id.
     * @param int|null $storeId    Store id. Null to load the category default values.
     *
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function get(int $categoryId, ?int $storeId = null): CategoryInterface
    {
        $storeKey = $storeId ?? 'all';

        if (!isset($this->categories[$storeKey][$categoryId])) {
            $category = $this->categoryFactory->create();
            if (null !== $storeId) {
                $category->setStoreId($storeId);
            }
            $category->load($categoryId);
            if (!$category->getId()) {
                throw NoSuchEntityException::singleField('id', $categoryId);
            }
            $this->categories[$storeKey][$categoryId] = $category;
        }

        return $this->categories[$storeKey][$categoryId];
    }

    /**
     * Forget the loaded categories (eg. once some categories have been saved in the current process).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->categories = [];
    }
}
