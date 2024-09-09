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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin which is modified the behavior of sorting arrows based on the custom sort direction attribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class SortDirectionPerCategoryPlugin
{
    const XML_PATH_LIST_DEFAULT_SORT_DIRECTION_BY = 'catalog/frontend/default_sort_direction_by';

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Http
     */
    private $request;

    /**
     * Scope configuration.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Toolbar constructor.
     *
     * @param CategoryRepositoryInterface $categoryRepository Category Repository.
     * @param Http                        $request            Http request.
     * @param ScopeConfigInterface        $scopeConfig        Scope configuration.
     * @param StoreManagerInterface       $storeManager       Store manager.
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        Http $request,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Modify sorting direction before setting the collection in the toolbar.
     *
     * @param ProductListToolbar $subject    Product list toolbar.
     * @param mixed              $collection Collection.
     *
     * @return array
     * @throws NoSuchEntityException
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
     * Retrieve Product List Default Sort Direction By
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function getProductListDefaultSortDirectionBy()
    {
        // Get the current store ID.
        $storeId = $this->storeManager->getStore()->getId();

        // Fetch system configuration value for 'default_sort_direction_by' at the store level.
        return $this->scopeConfig->getValue(
            self::XML_PATH_LIST_DEFAULT_SORT_DIRECTION_BY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the custom sort direction from the current category.
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function getCustomSortDirection()
    {
        $categoryId = $this->request->getParam('id');

        if (!$categoryId) {
            return $this->getProductListDefaultSortDirectionBy(); // Fallback to system config value if no category ID.
        }

        try {
            $category = $this->categoryRepository->get($categoryId);

            // Check if the category has a custom sort direction set.
            $customDirection = $category->getSortDirection();

            // If a custom sort direction exists for the category and is valid, return it.
            if ($customDirection && in_array($customDirection, ['asc', 'desc'])) {
                return $customDirection;
            }
        } catch (\Exception $e) {
            // Handle exceptions (e.g., category not found) by falling back to the system config.
            return $this->getProductListDefaultSortDirectionBy();
        }

        // If no custom sort direction for the category, return the default system config.
        return $this->getProductListDefaultSortDirectionBy();
    }
}
