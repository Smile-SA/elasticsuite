<?php
/**
 * DISCLAIMER
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
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
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
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\Builder
     */
    private $queryBuilder;

    /**
     * @var ContainerConfigurationFactory
     */
    private $containerConfigFactory;

    /**
     * Constructor.
     *
     * @param QueryInterface                $searchQuery              Search query to preview.
     * @param FulltextCollectionFactory     $productCollectionFactory Fulltext product collection factory.
     * @param ItemDataFactory               $previewItemFactory       Preview item factory.
     * @param QueryFactory                  $queryFactory             ES query factory.
     * @param QueryBuilder                  $queryBuilder             Query Builder.
     * @param ContainerConfigurationFactory $containerConfigFactory   Container Config Factory.
     * @param int                           $size                     Preview size.
     * @param string                        $search                   Preview search.
     */
    public function __construct(
        QueryInterface $searchQuery,
        FulltextCollectionFactory $productCollectionFactory,
        ItemDataFactory $previewItemFactory,
        QueryFactory $queryFactory,
        QueryBuilder $queryBuilder,
        ContainerConfigurationFactory $containerConfigFactory,
        $size = 10,
        $search = ''
    ) {
        parent::__construct($productCollectionFactory, $previewItemFactory, $queryFactory, $searchQuery->getStoreId(), $size, $search);
        $this->searchQuery            = $searchQuery;
        $this->queryFactory           = $queryFactory;
        $this->search                 = $search;
        $this->queryBuilder           = $queryBuilder;
        $this->containerConfigFactory = $containerConfigFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        $data = $this->getUnsortedProductData();

        $sortedProducts   = $this->getSortedProducts();
        $data['products'] = $this->preparePreviewItems(array_merge($sortedProducts, $data['products']));

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareProductCollection(Collection $collection): Collection
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
    protected function getSortedProductIds(): array
    {
        return $this->searchQuery->getSortedProductIds();
    }

    /**
     * Return a collection with with products that match the current preview.
     *
     * @return array
     */
    private function getUnsortedProductData(): array
    {
        $productCollection = $this->getProductCollection()->setPageSize($this->size);

        if (!in_array($this->search, [null, ''], true)) {
            $productCollection->setSearchQuery($this->searchQuery->getQueryText());
            $fulltextQueryFilter = $this->getFullTextQuery();
            $productCollection->addQueryFilter($fulltextQueryFilter);
        }

        return ['products' => $productCollection->getItems(), 'size' => $productCollection->getSize()];
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getFullTextQuery()
    {
        $storeId = $this->searchQuery->getStoreId();

        return $this->queryBuilder->createQuery(
            $this->getRequestContainerConfiguration($storeId, 'quick_search_container'),
            $this->search,
            [],
            SpellcheckerInterface::SPELLING_TYPE_EXACT
        );
    }

    /**
     * Load the search request configuration (index, type, mapping, ...) using the search request container name.
     *
     * @param integer $storeId       Store id.
     * @param string  $containerName Search request container name.
     *
     * @return ContainerConfigurationInterface
     * @throws \LogicException Thrown when the search container is not found into the configuration.
     */
    private function getRequestContainerConfiguration($storeId, $containerName)
    {
        $config = $this->containerConfigFactory->create(
            ['containerName' => $containerName, 'storeId' => $storeId]
        );

        if ($config === null) {
            throw new \LogicException("No configuration exists for request {$containerName}");
        }

        return $config;
    }
}
