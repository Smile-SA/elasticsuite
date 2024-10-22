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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\RequestInterface;
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
     * Default customer group id.
     */
    const DEFAULT_CUSTOMER_GROUP_ID = '0';

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
     * @var \Magento\Framework\App\RequestInterface|mixed
     */
    private $request;

    /**
     * @var \Magento\Catalog\Model\Config|mixed
     */
    private $categoryConfig;

    /**
     * @var string
     */
    private $sortBy;

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
     * @param RequestInterface          $request                  HTTP Request.
     * @param Config                    $categoryConfig           Category config.
     */
    public function __construct(
        CategoryInterface $category,
        FulltextCollectionFactory $productCollectionFactory,
        ItemDataFactory $previewItemFactory,
        QueryFactory $queryFactory,
        ContextInterface $searchContext,
        $size = 10,
        $search = '',
        RequestInterface $request = null,
        Config $categoryConfig = null
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $category->getStoreId(), $size, $search);
        $this->category      = $category;
        $this->queryFactory  = $queryFactory;
        $this->searchContext = $searchContext;
        $this->request       = $request ?: \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
        $this->categoryConfig = $categoryConfig ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Config::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(Collection $collection) : Collection
    {
        $this->searchContext->setIsBlacklistingApplied(false);
        $this->searchContext->setCurrentCategory($this->category);
        $this->searchContext->setStoreId($this->category->getStoreId());
        $collection->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);

        $queryFilter = $this->getQueryFilter();
        if ($queryFilter !== null) {
            $collection->addQueryFilter($queryFilter);
        }

        $sortBy            = $this->getSortBy() ?? 'position';
        $directionFallback = $sortBy !== 'position' ? Collection::SORT_ORDER_ASC : Collection::SORT_ORDER_DESC;

        $direction = $this->request->getParam('sort_direction', $directionFallback);
        if (empty($direction) || ((string) $direction === '')) {
            $direction = $directionFallback;
        }
        $collection->setOrder($sortBy, $direction);
        $collection->addPriceData(self::DEFAULT_CUSTOMER_GROUP_ID, $this->category->getStore()->getWebsiteId());

        return $collection;
    }

    /**
     * Return the list of sorted product ids.
     *
     * @return array
     */
    protected function getSortedProductIds() : array
    {
        return ($this->getSortBy() === 'position') ? $this->category->getSortedProductIds() : [];
    }

    /**
     * {@inheritDoc}
     */
    protected function preparePreviewItems($products = []): array
    {
        $items = parent::preparePreviewItems($products);

        if ($this->getSortBy() !== 'position') {
            // In order to sort the product in admin category grid, we need to set the position value
            // if the sort order is different from position because the products are sorted in js.
            // We also disable manual sorting when sort order is not position.
            array_walk($items, function (&$productData, $index) {
                $productData['position']            = $index;
                $productData['can_use_manual_sort'] = false;
            });
        }

        return $items;
    }

    /**
     * Return the filter applied to the query.
     *
     * @return QueryInterface|null
     */
    private function getQueryFilter(): ?QueryInterface
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
    private function getEntityIdFilterQuery($ids): QueryInterface
    {
        return $this->queryFactory->create(QueryInterface::TYPE_TERMS, ['field' => 'entity_id', 'values' => $ids]);
    }

    /**
     * Get sort by attribute.
     *
     * @return string
     */
    private function getSortBy() : string
    {
        if (!$this->sortBy) {
            $useConfig = $this->request->getParam('use_config', []);
            $useConfig = array_key_exists('default_sort_by', $useConfig) && $useConfig['default_sort_by'] == 'true';
            $defaultSortBy = $this->categoryConfig->getProductListDefaultSortBy();
            $this->sortBy = $useConfig ? $defaultSortBy : $this->request->getParam('default_sort_by');
        }

        return $this->sortBy;
    }
}
