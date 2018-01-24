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

namespace Smile\ElasticsuiteCatalog\Model\Search;

use Magento\Search\Model\QueryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\ItemFactory;
use Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview;

/**
 * Search result preview model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends \Smile\ElasticsuiteCatalog\Model\ProductSorter\AbstractPreview
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
     * Constructor.
     *
     * @param QueryInterface            $searchQuery              Search query to preview.
     * @param FulltextCollectionFactory $productCollectionFactory Fulltext product collection factory.
     * @param ItemFactory               $previewItemFactory       Preview item factory.
     * @param QueryFactory              $queryFactory             ES query factory.
     * @param int                       $size                     Preview size.
     */
    public function __construct(
        QueryInterface $searchQuery,
        FulltextCollectionFactory $productCollectionFactory,
        ItemFactory $previewItemFactory,
        QueryFactory $queryFactory,
        $size = 10
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $searchQuery->getStoreId(), $size);
        $this->searchQuery  = $searchQuery;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        $collection->setVisibility([Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH]);
        $collection->addSearchFilter($this->searchQuery->getQueryText());

        return $collection;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    protected function getSortedProductIds()
    {
        return $this->searchQuery->getSortedProductIds();
    }
}
