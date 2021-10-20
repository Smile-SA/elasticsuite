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
     * ProductsListPlugin constructor.
     *
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param CategoryRepositoryInterface $categoryRepository Category repository.
     * @param Conditions                  $conditionsHelper   Condition helper.
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        Conditions $conditionsHelper
    ) {
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->conditionsHelper = $conditionsHelper;
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

        if ($subject->getData('condition_option') == 'condition' || !$subject->getData('condition_option')) {
            $conditions = $subject->getData('conditions_encoded') ?: $subject->getData('conditions');
            if ($conditions) {
                $conditions = $this->conditionsHelper->decode($conditions);
            }
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

        return $collection;
    }
}
