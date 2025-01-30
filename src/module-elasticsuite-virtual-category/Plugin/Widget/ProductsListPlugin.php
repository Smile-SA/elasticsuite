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
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\CollectionFactory;
use \Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition\Builder as SkuPositionSortOrderBuilder;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

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
     * @var Provider
     */
    private $filterProvider;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * ProductsListPlugin constructor.
     *
     * @param StoreManagerInterface       $storeManager                Store manager.
     * @param CategoryRepositoryInterface $categoryRepository          Category repository.
     * @param Conditions                  $conditionsHelper            Condition helper.
     * @param SkuPositionSortOrderBuilder $skuPositionSortOrderBuilder Sort order builder for sku_position.
     * @param Provider                    $filterProvider              Filter provider.
     * @param QueryFactory                $queryFactory                Query factory.
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        Conditions $conditionsHelper,
        SkuPositionSortOrderBuilder $skuPositionSortOrderBuilder,
        Provider $filterProvider,
        QueryFactory $queryFactory
    ) {
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->conditionsHelper = $conditionsHelper;
        $this->skuPositionSortOrderBuilder = $skuPositionSortOrderBuilder;
        $this->filterProvider = $filterProvider;
        $this->queryFactory = $queryFactory;
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
        // Get the current store ID.
        $storeId = $this->getCurrentStoreId($subject);

        // Set the store ID back to the widget.
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
        $storeId    = $this->getCurrentStoreId($subject);
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
                        if ($condition['attribute'] == 'category_ids' && array_key_exists('value', $condition)) {
                            $filterQueries = [];
                            $categoryIds = array_map("trim", explode(',', (string) $condition['value']));
                            foreach ($categoryIds as $categoryId) {
                                try {
                                    $category = $this->categoryRepository->get($categoryId, $storeId);
                                    $queryFilter = $this->filterProvider->getQueryFilter($category);
                                    if ($queryFilter !== null) {
                                        $filterQueries[] = $queryFilter;
                                    }
                                } catch (NoSuchEntityException $exception) {
                                    continue;
                                }
                            }
                            if (!empty($filterQueries)) {
                                $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $filterQueries]);

                                if (substr($condition['operator'], 0, 1) === '!') {
                                    $query = $this->applyNegation($query);
                                }

                                $collection->addQueryFilter($query);
                            }
                        }
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * Get the current store ID from the widget's data or default store.
     *
     * @param ProductsList $subject Widget product list.
     * @return int
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreId(ProductsList $subject)
    {
        // Get the store ID from the widget's data.
        $storeId = (int) $subject->getData('store_id');

        // If the store ID is not specified (0), use the default store ID from the store manager.
        if ($storeId === Store::DEFAULT_STORE_ID) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $storeId;
    }

    /**
     * Instantiate query from type and params.
     *
     * @param string $queryType   Query type.
     * @param array  $queryParams Query instantiation params.
     *
     * @return QueryInterface
     */
    private function prepareQuery($queryType, $queryParams)
    {
        return $this->queryFactory->create($queryType, $queryParams);
    }

    /**
     * Apply a negation to the current query.
     *
     * @param QueryInterface $query Negated query.
     *
     * @return QueryInterface
     */
    private function applyNegation(QueryInterface $query)
    {
        return $this->prepareQuery(QueryInterface::TYPE_NOT, ['query' => $query]);
    }
}
