<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model;

use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Preview\ItemFactory;

/**
 * Virtual category preview model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview
{
    /**
     * @var FulltextCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ItemFactory
     */
    private $previewItemFactory;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     *
     * @var unknown
     */
    private $size;

    /**
     * Constructor.
     *
     * @param CategoryInterface         $category                 Category to preview.
     * @param FulltextCollectionFactory $productCollectionFactory Fulltext product collection factory.
     * @param ItemFactory               $previewItemFactory       Preview item factory.
     * @param QueryFactory              $queryFactory             QueryInterface factory.
     * @param int                       $size                     Preview size.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ItemFactory $previewItemFactory,
        QueryFactory $queryFactory,
        $size = 10
    ) {
        $this->size                     = $size;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->previewItemFactory       = $previewItemFactory;
        $this->category                 = $category;
        $this->queryFactory             = $queryFactory;
    }

    /**
     * Load preview data.
     *
     * @return array
     */
    public function getData()
    {
        $data = $this->getUnsortedProductData();

        $sortedProducts = $this->getSortedProducts();
        $data['products'] = $this->preparePreviewItems(array_merge($sortedProducts, $data['products']));

        return $data;
    }

    /**
     * Preview base product collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    private function getProductCollection()
    {
        $productCollection = $this->productCollectionFactory->create();
        $queryFilter       = $this->getQueryFilter();

        $productCollection->setStoreId($this->category->getStoreId())
            ->addAttributeToSelect(['name', 'small_image']);

        if ($queryFilter !== null) {
            $productCollection->addQueryFilter($queryFilter);
        }

        return $productCollection;
    }

    /**
     * Return a collection with with products that match the category rules loaded.
     *
     * @return array
     */
    private function getUnsortedProductData()
    {
        $productCollection = $this->getProductCollection()->setPageSize($this->size);

        return ['products' => $productCollection->getItems(), 'size' => $productCollection->getSize()];
    }

    /**
     * Return a collection with all products manually sorted loaded.
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getSortedProducts()
    {
        $products   = [];
        $productIds = $this->getSortedProductIds();

        if ($productIds && count($productIds)) {
            $productCollection = $this->getProductCollection()->setPageSize(count($productIds));

            $idFilterParams = ['values' => $productIds, 'field' => 'entity_id'];
            $idFilter       = $this->queryFactory->create(QueryInterface::TYPE_TERMS, $idFilterParams);
            $productCollection->addQueryFilter($idFilter);

            $productCollection->setPageSize(count($productIds));

            $products = $productCollection->getItems();
        }

        return $products;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    private function getSortedProductIds()
    {
        return $this->category->getSortedProductIds();
    }

    /**
     * Convert an array of products to an array of preview items.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product[] $products Product list.
     *
     * @return Preview\Item[]
     */
    private function preparePreviewItems($products = [])
    {
        $items = [];

        foreach ($products as $product) {
            $item = $this->previewItemFactory->create(['product' => $product]);
            $items[$product->getId()] = $item->getData();
        }

        return array_values($items);
    }

    /**
     * Return the filter applied to the query.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @return QueryInterface
     */
    private function getQueryFilter()
    {
        $query = null;

        $this->category->setIsActive(true);

        if ($this->category->getIsVirtualCategory() || $this->category->getId()) {
            $query = $this->category->getVirtualRule()->getCategorySearchQuery($this->category);
        }

        if ((bool) $this->category->getIsVirtualCategory() === false) {
            $queryParams = [];

            if ($query !== null) {
                $queryParams['should'][] = $query;
            }

            $idFilters = [
                'should'  => $this->category->getAddedProductIds(),
                'mustNot' => $this->category->getDeletedProductIds(),
            ];

            foreach ($idFilters as $clause => $productIds) {
                if ($productIds && !empty($productIds)) {
                    $queryParams[$clause][] = $this->getEntityIdFilterQuery($productIds);
                }
            }

            $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);
        }

        return $query;
    }

    /**
     * Create a product id filter query.
     *
     * @param array $ids Id to be filtered.
     *
     * @return QueryInterface
     */
    private function getEntityIdFilterQuery($ids)
    {
        return $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'entity_id', 'values' => $ids]);
    }
}
