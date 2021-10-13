<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Widget;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PageBuilder\Model\Catalog\Sorting;
use Magento\PageBuilder\Plugin\Catalog\Block\Product\ProductsListPlugin as PageBuilderPlugin;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\CollectionFactory;

/**
 * Apply virtual category rule on widget collection.
 * This plugin inherits from \Magento\PageBuilder\Plugin\Catalog\Block\Product\ProductsListPlugin
 * because we need to disable pagebuilder plugin to prevent it to add category filter
 * and we inherit from it in order to preserve other plugged method than "afterCreateCollection".
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class ProductsListPlugin extends PageBuilderPlugin
{
    /**
     * @var Provider
     */
    private $filterProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Sorting
     */
    private $sorting;

    /**
     * @var Stock
     */
    private $stock;

    /**
     * @var Conditions
     */
    private $conditionsHelper;

    /**
     * ProductsListPlugin constructor.
     *
     * @param Provider                    $filterProvider     Filter Provider.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param CategoryRepositoryInterface $categoryRepository Category repository.
     * @param Sorting                     $sorting            Catalog sorting.
     * @param Stock                       $stock              Stock manager.
     * @param Conditions                  $conditionsHelper   Condition helper.
     */
    public function __construct(
        Provider $filterProvider,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        Sorting $sorting,
        Stock $stock,
        Conditions $conditionsHelper
    ) {
        parent::__construct($sorting, $stock, $categoryRepository);
        $this->filterProvider = $filterProvider;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->sorting = $sorting;
        $this->stock = $stock;
        $this->conditionsHelper = $conditionsHelper;
    }

    /**
     * Apply virtual category rule on widget collection.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ProductsList $subject    Widget product list.
     * @param Collection   $collection Product collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws NoSuchEntityException
     */
    public function afterCreateCollection(ProductsList $subject, $collection)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $collection->setStoreId($storeId);
        $this->stock->addIsInStockFilterToCollection($collection);

        $conditions = $subject->getData('conditions_encoded') ?: $subject->getData('conditions');
        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
        }

        foreach ($conditions as $condition) {
            if (!empty($condition['attribute'])) {
                if ($condition['attribute'] == 'category_ids') {
                    if (array_key_exists('value', $condition)) {
                        $categoryId = $condition['value'];
                        $this->applyCategoryFilter($collection, $categoryId, $storeId);
                    }
                }
            }
        }

        $sortOption = $subject->getData('sort_order');
        if (!empty($sortOption)) {
            $collection = $this->sorting->applySorting($sortOption, $collection);
        }

        return $collection;
    }

    /**
     * Apply category filter.
     *
     * @param Collection $collection Product collection.
     * @param int        $categoryId Category id.
     * @param int        $storeId    Store id.
     */
    protected function applyCategoryFilter($collection, $categoryId, $storeId)
    {
        try {
            $category = $this->categoryRepository->get($categoryId, $storeId);
        } catch (NoSuchEntityException $e) {
            $category = null;
        }

        if ($category && $category->getData('is_virtual_category')) {
            $query = $this->filterProvider->getQueryFilter($category);
            if ($query !== null) {
                $collection->addQueryFilter($query);
            }
        } elseif ($category) {
            $collection->addCategoryFilter($category);
        }
    }
}
