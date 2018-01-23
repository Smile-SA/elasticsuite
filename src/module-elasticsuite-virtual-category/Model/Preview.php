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

use Magento\Catalog\Model\Product\Visibility;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\ItemFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview;

/**
 * Virtual category preview model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends \Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview
{
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
     * @param int                       $size                     Preview size.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ItemFactory $previewItemFactory,
        QueryFactory $queryFactory,
        $size = 10
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $category->getStoreId(), $size);
        $this->category     = $category;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        $collection->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);

        $queryFilter = $this->getQueryFilter();
        if ($queryFilter !== null) {
            $collection->addQueryFilter($queryFilter);
        }

        return $collection;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    protected function getSortedProductIds()
    {
        return $this->category->getSortedProductIds();
    }

    /**
     * Return the filter applied to the query.
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
