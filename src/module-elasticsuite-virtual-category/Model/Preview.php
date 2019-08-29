<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model;

use Magento\Catalog\Model\Product\Visibility;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\ItemDataFactory;

/**
 * Virtual category preview model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends AbstractPreview
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
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param CategoryInterface         $category                 Category to preview.
     * @param FulltextCollectionFactory $productCollectionFactory Fulltext product collection factory.
     * @param ItemDataFactory           $previewItemFactory       Preview item factory.
     * @param QueryFactory              $queryFactory             QueryInterface factory.
     * @param ContextInterface          $searchContext            Search Context
     * @param int                       $size                     Preview size.
     * @param string                    $search                   Preview search.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ItemDataFactory $previewItemFactory,
        QueryFactory $queryFactory,
        ContextInterface $searchContext,
        $size = 10,
        $search = ''
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $category->getStoreId(), $size, $search);
        $this->category      = $category;
        $this->queryFactory  = $queryFactory;
        $this->searchContext = $searchContext;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(Collection $collection) : Collection
    {
        $this->searchContext->setCurrentCategory($this->category);
        $this->searchContext->setStoreId($this->category->getStoreId());
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
    protected function getSortedProductIds() : array
    {
        return $this->category->getSortedProductIds();
    }

    /**
     * Return the filter applied to the query.
     *
     * @return QueryInterface
     */
    private function getQueryFilter() : QueryInterface
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
    private function getEntityIdFilterQuery($ids) : QueryInterface
    {
        return $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'entity_id', 'values' => $ids]);
    }
}
