<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Rss;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Category RSS data provider plugin
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class CategoryPlugin
{
    /**
     * Apply category filter to the collection
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Model\Rss\Category                     $dataProvider Data provider.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection   Product collection.
     * @param \Magento\Catalog\Model\Category                         $category     Current category.
     * @param int                                                     $storeId      Store ID.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function afterGetProductCollection(
        \Magento\Catalog\Model\Rss\Category $dataProvider,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        \Magento\Catalog\Model\Category $category,
        $storeId
    ) {
        $collection->addFieldToFilter('category_ids', $this->getCategoryFilterParam($category));

        return $collection;
    }

    /**
     * Return category filter param
     *
     * @param \Magento\Catalog\Model\Category $category Category.
     *
     * @return int|QueryInterface
     */
    private function getCategoryFilterParam(\Magento\Catalog\Model\Category $category)
    {
        $filterParam = $category->getId();

        if ($category->getVirtualRule()) { // Implicit dependency to Virtual Categories module.
            $category->setIsActive(true);

            $filterParam = $category->getVirtualRule()->getCategorySearchQuery($category);
        }

        return $filterParam;
    }
}
