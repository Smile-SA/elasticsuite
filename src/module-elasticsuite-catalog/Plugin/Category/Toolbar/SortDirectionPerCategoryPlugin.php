<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Category\Toolbar;

use Magento\Catalog\Block\Product\ProductList\Toolbar as ProductListToolbar;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Request\Http;

/**
 * Plugin which is modified the behavior of sorting arrows based on the custom sort direction attribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class SortDirectionPerCategoryPlugin
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Http
     */
    private $request;

    /**
     * Toolbar constructor.
     *
     * @param CategoryRepositoryInterface $categoryRepository Category Repository.
     * @param Http                        $request            Http request.
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        Http $request
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->request = $request;
    }

    /**
     * Modify sorting direction before setting the collection in the toolbar.
     *
     * @param ProductListToolbar $subject
     * @param mixed $collection
     *
     * @return array
     */
    public function beforeSetCollection(ProductListToolbar $subject, $collection)
    {
        $customDirection = $this->getCustomSortDirection();

        if ($customDirection) {
            $subject->setDefaultDirection($customDirection);
        }

        return [$collection];
    }

    /**
     * Get the custom sort direction from the current category.
     *
     * @return string|null
     */
    private function getCustomSortDirection()
    {
        $categoryId = $this->request->getParam('id');

        if (!$categoryId) {
            return null; // Return null if category ID is not available.
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
            $customDirection = $category->getSortDirection();

            if ($customDirection && in_array($customDirection, ['asc', 'desc'])) {
                return $customDirection;
            }
        } catch (\Exception $e) {
            return null; // Handle category not found or other exceptions.
        }

        return null;
    }
}
