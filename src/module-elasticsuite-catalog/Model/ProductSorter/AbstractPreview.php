<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ProductSorter;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as ProductCollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product sorter preview.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractPreview implements PreviewInterface
{
    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ItemDataFactory
     */
    private $itemFactory;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var string
     */
    private $search;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param ProductCollectionFactory $collectionFactory Product collection factory.
     * @param ItemDataFactory          $itemFactory       Preview item factory.
     * @param QueryFactory             $queryFactory      ES query factory.
     * @param integer                  $storeId           Store id.
     * @param integer                  $size              Preview size.
     * @param string                   $search            Preview search.
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        ItemDataFactory $itemFactory,
        QueryFactory $queryFactory,
        $storeId,
        $size = 10,
        $search = ''
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->itemFactory       = $itemFactory;
        $this->queryFactory      = $queryFactory;
        $this->storeId           = $storeId;
        $this->size              = $size;
        $this->search            = $search;
    }

    /**
     * {@inheritDoc}
     */
    public function getData() : array
    {
        $data = $this->getUnsortedProductData();

        $sortedProducts = $this->getSortedProducts();
        $data['products'] = $this->preparePreviewItems(array_merge($sortedProducts, $data['products']));

        return $data;
    }

    /**
     * Apply custom logic to product collection.
     *
     * @param Collection $collection Product collection.
     *
     * @return Collection
     */
    protected function prepareProductCollection(Collection $collection) : Collection
    {
        return $collection;
    }

    /**
     * List of sorted product ids.
     *
     * @return array
     */
    abstract protected function getSortedProductIds() : array;

    /**
     * Convert an array of products to an array of preview items.
     *
     * @param Product[] $products Product list.
     *
     * @return array
     */
    protected function preparePreviewItems($products = []) : array
    {
        $items = [];

        foreach ($products as $product) {
            $items[$product->getId()] = $this->itemFactory->getData($product);
        }

        return array_values($items);
    }

    /**
     * Preview base product collection.
     *
     * @return Collection
     */
    protected function getProductCollection() : Collection
    {
        $productCollection = $this->collectionFactory->create();

        $productCollection->setStoreId($this->storeId)
            ->addAttributeToSelect(['name', 'small_image']);

        return $this->prepareProductCollection($productCollection);
    }

    /**
     * Return a collection with all products manually sorted loaded.
     *
     * @return ProductInterface[]
     */
    protected function getSortedProducts() : array
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

    /**
     * Return a collection with with products that match the current preview.
     *
     * @return array
     */
    private function getUnsortedProductData() : array
    {
        $productCollection = $this->getProductCollection()->setPageSize($this->size);

        if (!in_array($this->search, [null, ''], true)) {
            $productCollection->setSearchQuery($this->search);
        }

        return ['products' => $productCollection->getItems(), 'size' => $productCollection->getSize()];
    }
}
