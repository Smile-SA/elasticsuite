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
     * Constructor.
     *
     * @param CategoryInterface         $category                 Category to preview.
     * @param FulltextCollectionFactory $productCollectionFactory Fulltext product collection factory.
     * @param ItemFactory               $previewItemFactory       Preview item factory.
     * @param QueryFactory              $queryFactory             QueryInterface factory.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ItemFactory $previewItemFactory,
        QueryFactory $queryFactory
    ) {
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
        $productCollection = $this->productCollectionFactory->create();

        $productCollection
            ->addQueryFilter($this->getFilterQuery())
            ->setStoreId($this->category->getStoreId())
            ->addAttributeToSelect(['name', 'price', 'small_image']);
        $productCollection->setPageSize(20);

        return ['products' => $this->loadItems($productCollection), 'size' => $productCollection->getSize()];
    }

    /**
     * Load items data from the collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection Product collection.
     *
     * @return Preview\Item[]
     */
    private function loadItems(\Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection)
    {
        $items = [];

        foreach ($productCollection as $product) {
            $item = $this->previewItemFactory->create(['product' => $product]);
            $items[] = $item->getData();
        }

        return $items;
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
