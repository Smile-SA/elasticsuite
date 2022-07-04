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
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\CollectionFactory;
use \Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition\Builder as SkuPositionSortOrderBuilder;

/**
 * Apply category filter on widget collection.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class ProductsListPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Conditions
     */
    private $conditionsHelper;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition\Builder
     */
    private $skuPositionSortOrderBuilder;

    /**
     * ProductsListPlugin constructor.
     *
     * @param StoreManagerInterface       $storeManager                Store manager.
     * @param CategoryRepositoryInterface $categoryRepository          Category repository.
     * @param Conditions                  $conditionsHelper            Condition helper.
     * @param SkuPositionSortOrderBuilder $skuPositionSortOrderBuilder Sort order builder for sku_position
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        Conditions $conditionsHelper,
        SkuPositionSortOrderBuilder $skuPositionSortOrderBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->conditionsHelper = $conditionsHelper;
        $this->skuPositionSortOrderBuilder = $skuPositionSortOrderBuilder;
    }

    /**
     * Fix backend preview default store.
     *
     * @param ProductsList $subject Widget product list.
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeCreateCollection(ProductsList $subject)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $subject->setData('store_id', $storeId);

        return [];
    }

    /**
     * Apply virtual category rule on widget collection.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param ProductsList $subject    Widget product list.
     * @param Collection   $collection Product collection.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws NoSuchEntityException
     */
    public function afterCreateCollection(ProductsList $subject, $collection)
    {
        $storeId    = $this->storeManager->getStore()->getId();
        $sortOption = $subject->getData('sort_order');
        $conditionOption = $subject->getData('condition_option');

        // Manage legacy products selection by "category" and sorting by "position".
        // This sorting should keep the position of the products in the same order they were sorted in the category.
        if (($conditionOption === 'category_ids') && ($sortOption === 'position')) {
            $categoryId = $subject->getData('condition_option_value');
            if ($categoryId) {
                $collection->addSortFilterParameters(
                    'position',
                    'category.position',
                    'category',
                    ['category.category_id' => $categoryId]
                );
            }
        } elseif (($conditionOption === 'sku') && ($sortOption === 'position_by_sku')) {
            // Manage legacy products selection by "sku" and sorting by "position_by_sku".
            // This sorting should keep the skus sorted in the same order they were contributed.
            if ((string) $subject->getData('condition_option_value') !== '') {
                $skus = array_map("trim", explode(',', (string) $subject->getData('condition_option_value')));
                if (!empty($skus)) {
                    $sortOrder = $this->skuPositionSortOrderBuilder->buildSortOrder($skus);
                    $attribute = key($sortOrder);
                    $dir       = current($sortOrder);
                    $collection->setOrder($attribute, $dir);
                }
            }
        } elseif ($conditionOption == 'condition' || !$conditionOption) {
            // Manage legacy products selection by "condition".
            $conditions = $subject->getData('conditions_encoded') ?: $subject->getData('conditions');
            if ($conditions) {
                $conditions = $this->conditionsHelper->decode($conditions);
                foreach ($conditions as $condition) {
                    if (!empty($condition['attribute'])) {
                        if ($condition['attribute'] == 'category_ids') {
                            if (array_key_exists('value', $condition)) {
                                $categoryId = $condition['value'];
                                try {
                                    $category = $this->categoryRepository->get($categoryId, $storeId);
                                    $collection->addCategoryFilter($category);
                                } catch (NoSuchEntityException $exception) {
                                    $category = null;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $collection;
    }
}
