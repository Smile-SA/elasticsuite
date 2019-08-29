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

namespace Smile\ElasticsuiteCatalog\Model\Search;

use Magento\Search\Model\QueryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\ItemDataFactory;

/**
 * Search result preview model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends AbstractPreview
{
    /**
     * @var QueryInterface
     */
    private $searchQuery;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var string
     */
    private $search;

    /**
     * Constructor.
     *
     * @param QueryInterface            $searchQuery              Search query to preview.
     * @param FulltextCollectionFactory $productCollectionFactory Fulltext product collection factory.
     * @param ItemDataFactory           $previewItemFactory       Preview item factory.
     * @param QueryFactory              $queryFactory             ES query factory.
     * @param int                       $size                     Preview size.
     * @param string                    $search                   Preview search.
     */
    public function __construct(
        QueryInterface $searchQuery,
        FulltextCollectionFactory $productCollectionFactory,
        ItemDataFactory $previewItemFactory,
        QueryFactory $queryFactory,
        $size = 10,
        $search = ''
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $searchQuery->getStoreId(), $size, $search);
        $this->searchQuery  = $searchQuery;
        $this->queryFactory = $queryFactory;
        $this->search = $search;
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
     * {@inheritDoc}
     */
    protected function prepareProductCollection(Collection $collection) : Collection
    {
        $collection->setVisibility([Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH]);
        $collection->setSearchQuery($this->searchQuery->getQueryText());

        return $collection;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    protected function getSortedProductIds() : array
    {
        return $this->searchQuery->getSortedProductIds();
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
            $productCollection->setSearchQuery($this->searchQuery->getQueryText() . ' ' . $this->search);
        }

        return ['products' => $productCollection->getItems(), 'size' => $productCollection->getSize()];
    }
}
