<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ProductSorter;

use \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as ProductCollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

abstract class AbstractPreview implements PreviewInterface
{
    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    public function __construct(
        ProductCollectionFactory $collectionFactory,
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        $storeId,
        $size = 10
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->itemFactory       = $itemFactory;
        $this->queryFactory      = $queryFactory;
        $this->storeId           = $storeId;
        $this->size              = $size;
    }

    public function getData()
    {
        $data = $this->getUnsortedProductData();

        $sortedProducts = $this->getSortedProducts();
        $data['products'] = $this->preparePreviewItems(array_merge($sortedProducts, $data['products']));

        return $data;
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
            $item = $this->itemFactory->create(['product' => $product]);
            $items[$product->getId()] = $item->getData();
        }

        return array_values($items);
    }

    /**
     * Preview base product collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    private function getProductCollection()
    {
        $productCollection = $this->collectionFactory->create();

        $productCollection->setStoreId($this->storeId)
            ->addAttributeToSelect(['name', 'small_image']);

        return $this->prepareProductCollection($productCollection);
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

            $products = $productCollection->getItems();
        }

        $sortedProducts = [];

        foreach ($this->getSortedProductIds() as $productId) {
            if (isset($products[$productId])) {
                $sortedProducts[$productId] = $products[$productId];
            }
        }

        return $sortedProducts;
    }

    protected function prepareProductCollection(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        return $collection;
    }

    abstract protected function getSortedProductIds();
}