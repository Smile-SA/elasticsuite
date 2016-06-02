<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteVirtualCategory\Model;

use Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticSuiteVirtualCategory\Model\Preview\ItemFactory;

/**
 * Virtual category preview model.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
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
        $manualSortProductCollection = $this->getManualSortProductCollection();
        $automaticProductCollection  = $this->getAutomaticSortProductCollection()->setPageSize($this->size);

        $loadedProducts = array_merge($automaticProductCollection->getItems(), $manualSortProductCollection->getItems());

        return ['products' => $this->loadItems($loadedProducts), 'size' => $automaticProductCollection->getSize()];
    }

    /**
     * Return a collection with with products that match the category rules loaded.
     *
     * @return \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    private function getAutomaticSortProductCollection()
    {
        $productCollection = $this->productCollectionFactory->create();

        $productCollection
            ->setStoreId($this->category->getStoreId())
            ->addQueryFilter($this->getFilterQuery())
            ->addAttributeToSelect(['name', 'small_image']);

        return $productCollection;
    }

    /**
     * Return a collection with all products manually sorted loaded.
     *
     * @return \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    private function getManualSortProductCollection()
    {
        $productIds = $this->getSortedProductIds();

        $productCollection = $this->getAutomaticSortProductCollection();
        $productCollection->setPageSize(count($productIds));

        $idFilter = $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['values' => $productIds, 'field' => 'entity_id']);
        $productCollection->addQueryFilter($idFilter);

        return $productCollection;
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
    private function loadItems($products = [])
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
    private function getFilterQuery()
    {
        $queryClauses = [];

        if ($this->category->getIsVirtualCategory()) {
            $queryClauses['must'][] = $this->category->getVirtualRule()->getCategorySearchQuery($this->category);
        } else {
            if (empty($this->category->getProductIds())) {
                $this->category->setProductIds([0]);
            }

            $queryClauses['should'][] = $this->queryFactory->create(
                QueryInterface::TYPE_TERMS,
                ['values' => $this->category->getProductIds(), 'field' => 'entity_id']
            );

            $childrenQueries = $this->category->getVirtualRule()->getSearchQueriesByChildren($this->category);
            foreach ($childrenQueries as $childrenQuery) {
                $queryClauses['should'][] = $childrenQuery;
            }
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryClauses);
    }
}
