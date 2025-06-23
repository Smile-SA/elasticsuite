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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext;

use Magento\Customer\Model\Group as CustomerGroup;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\MetricInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Search engine product collection.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * @var QueryResponse
     */
    private $queryResponse;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $requestBuilder;

    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * @var string|QueryInterface
     */
    private $query;

    /**
     * @var string
     */
    private $searchRequestName;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var QueryInterface[]
     */
    private $queryFilters = [];

    /**
     * @var array
     */
    private $facets = [];

    /**
     * @var boolean
     */
    private $isSpellchecked = false;

    /**
     * Pager page size backup variable.
     * Page size is always set to false in _renderFiltersBefore() after executing the query to Elasticsearch,
     * to be sure to pull correctly all matched products from the DB.
     * But it needs to be reset so low-level methods like getLastPageNumber() still work.
     *
     * @var integer|false
     */
    private $originalPageSize = false;

    /**
     * @var array
     */
    private $countByAttributeSet;
    /**
     * @var array
     */
    private $countByAttributeCode;

    /**
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory           Collection entity factory
     * @param \Psr\Log\LoggerInterface                                     $logger                  Logger.
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy           Db Fetch strategy.
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager            Event manager.
     * @param \Magento\Eav\Model\Config                                    $eavConfig               EAV configuration.
     * @param \Magento\Framework\App\ResourceConnection                    $resource                DB connection.
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory        Entity factory.
     * @param \Magento\Catalog\Model\ResourceModel\Helper                  $resourceHelper          Resource helper.
     * @param \Magento\Framework\Validator\UniversalFactory                $universalFactory        Standard factory.
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager            Store manager.
     * @param \Magento\Framework\Module\Manager                            $moduleManager           Module manager.
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State            $catalogProductFlatState Flat index state.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig             Store configuration.
     * @param \Magento\Catalog\Model\Product\OptionFactory                 $productOptionFactory    Product options factory.
     * @param \Magento\Catalog\Model\ResourceModel\Url                     $catalogUrl              Catalog URL resource model.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate              Timezone helper.
     * @param \Magento\Customer\Model\Session                              $customerSession         Customer session.
     * @param \Magento\Framework\Stdlib\DateTime                           $dateTime                Datetime helper.
     * @param \Magento\Customer\Api\GroupManagementInterface               $groupManagement         Customer group manager.
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder               $requestBuilder          Search request builder.
     * @param \Magento\Search\Model\SearchEngine                           $searchEngine            Search engine
     * @param RequestFieldMapper                                           $requestFieldMapper      Search request field mapper.
     * @param \Magento\Framework\DB\Adapter\AdapterInterface               $connection              Db Connection.
     * @param string                                                       $searchRequestName       Search request name.
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Smile\ElasticsuiteCore\Search\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,
        RequestFieldMapper $requestFieldMapper,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $searchRequestName = 'catalog_view_container'
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection
        );

        $this->requestBuilder     = $requestBuilder;
        $this->searchEngine       = $searchEngine;
        $this->requestFieldMapper = $requestFieldMapper;
        $this->searchRequestName  = $searchRequestName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $this->loadProductCounts();
        }

        return $this->_totalRecords;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->_isFiltersRendered = false;

        return parent::clear();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        if (!isset($this->_orders[$attribute]) || ($this->_orders[$attribute] !== $dir)) {
            $this->_orders[$attribute] = $dir;
            // Reset Filter Rendering, because otherwise the new ordering will not be picked up by ::_renderFiltersBefore.
            $this->_isFiltersRendered = false;
        }

        return $this;
    }

    /**
     * Reset the sort order.
     *
     * @return self
     */
    public function resetOrder()
    {
        $this->_orders = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurPage($page)
    {
        $this->_isFiltersRendered = false;

        return parent::setCurPage($page);
    }

    /**
     * {@inheritDoc}
     */
    public function setPageSize($size)
    {
        /*
         * Explicitely setting the page size to false or null is to be treated as having not set any page size.
         * That is: no pagination, all items are expected.
         */
        $size = ($size === null) ? false : $size;
        $this->_pageSize = $size;
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $field = $this->mapFieldName($field);
        $this->filters[$field] = $condition;
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        if ($attribute !== 'entity_id') {
            return $this->setOrder($attribute, $dir);
        }

        return $this;
    }

    /**
     * Append a prebuilt (QueryInterface) query filter to the collection.
     *
     * @param QueryInterface $queryFilter Query filter.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addQueryFilter(QueryInterface $queryFilter)
    {
        $this->queryFilters[] = $queryFilter;
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * Remove a specific field filter.
     *
     * @param string $field Field to remove the filter for.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function removeFieldFilter($field)
    {
        $field = $this->mapFieldName($field);
        unset($this->filters[$field]);
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * Remove all field filters.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function removeFieldFilters()
    {
        $this->filters = [];
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * Remove all previously added query filters.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function removeQueryFilters()
    {
        $this->queryFilters = [];
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * Set search query filter in the collection.
     *
     * @param string|QueryInterface $query Search query text.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function setSearchQuery($query)
    {
        $this->query = $query;
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * Add search query filter.
     *
     * @deprecated Replaced by setSearchQuery
     *
     * @param string $query Search query text.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addSearchFilter($query)
    {
        return $this->setSearchQuery($query);
    }

    /**
     * Return field faceted data from faceted search result.
     *
     * @param string $field Facet field.
     *
     * @return array
     */
    public function getFacetedData($field)
    {
        $this->_renderFilters();
        $result = [];
        $aggregations = $this->queryResponse->getAggregations();

        $bucket = $aggregations->getBucket($field);

        if ($bucket) {
            foreach ($bucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $result[$value->getValue()] = $metrics;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function addCategoryFilter(\Magento\Catalog\Model\Category $category)
    {
        $categoryId = $category->getId();
        if ($categoryId) {
            $this->addFieldToFilter('category_ids', $categoryId);
            $this->_productLimitationFilters['category_ids'] = $categoryId;
        }
        $this->_isFiltersRendered = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVisibility($visibility)
    {
        $this->addFieldToFilter('visibility', $visibility);

        return $this;
    }

    /**
     * Indicates if the collection is spellchecked or not.
     *
     * @return boolean
     */
    public function isSpellchecked()
    {
        return $this->isSpellchecked;
    }

    /**
     * Filter in stock product.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addIsInStockFilter()
    {
        $this->addFieldToFilter('stock.is_in_stock', true);

        return $this;
    }

    /**
     * Set param for a sort order.
     *
     * @param string $sortName     Sort order name (eg. position, ...).
     * @param string $sortField    Sort field.
     * @param string $nestedPath   Optional nested path for the sort field.
     * @param array  $nestedFilter Optional nested filter for the sort field.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addSortFilterParameters($sortName, $sortField, $nestedPath = null, $nestedFilter = null)
    {
        $sortParams = [];

        if (isset($this->_productLimitationFilters['sortParams'])) {
            $sortParams = $this->_productLimitationFilters['sortParams'];
        }

        $sortParams[$sortName] = [
            'sortField'    => $sortField,
            'nestedPath'   => $nestedPath,
            'nestedFilter' => $nestedFilter,
        ];

        $this->_productLimitationFilters['sortParams'] = $sortParams;

        return $this;
    }

    /**
     * Get actual page size if is defined or return all results.
     *
     * @return integer|false
     */
    public function getPageSize()
    {
        if ($this->_pageSize !== false) {
            return $this->_pageSize;
        }

        if ($this->originalPageSize !== false) {
            return $this->originalPageSize;
        }

        return $this->getSize();
    }

    /**
     * Retrieve collection last page number.
     *
     * @return int
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getLastPageNumber()
    {
        $collectionSize = (int) $this->getSize();
        if (0 === $collectionSize) {
            return 1;
        } elseif ($this->_pageSize) {
            return (int) ceil($collectionSize / $this->_pageSize);
        } elseif ($this->originalPageSize) {
            return (int) ceil($collectionSize / $this->originalPageSize);
        } else {
            return 1;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritdoc}
     */
    protected function _renderFiltersBefore()
    {
        $searchRequest = $this->prepareRequest();

        $this->queryResponse = $this->searchEngine->search($searchRequest);

        // Update the product count.
        $this->_totalRecords = $this->queryResponse->count();

        // Filter search results. The pagination has to be resetted since it is managed by the engine itself.
        $docIds = array_map(
            function (\Magento\Framework\Api\Search\Document $doc) {
                return (int) $doc->getId();
            },
            $this->queryResponse->getIterator()->getArrayCopy()
        );

        if (empty($docIds)) {
            $docIds[] = 0;
        }

        $this->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
        $this->getSelect()->where('e.entity_id IN (?)', ['in' => $docIds]);
        $orderList = join(',', $docIds);
        $this->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);
        $this->getSelect()->order(new \Zend_Db_Expr("FIELD(e.entity_id,$orderList)"));

        $this->originalPageSize = $this->getPageSize();

        $this->isSpellchecked = $searchRequest->isSpellchecked();

        parent::_renderFiltersBefore();
    }

    /**
     * Set _pageSize false since it is managed by the engine and might have been changed since _renderFiltersBefore.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _beforeLoad()
    {
        if ($this->_pageSize !== false) {
            $this->originalPageSize = $this->_pageSize;
            $this->_pageSize = false;
        }

        return parent::_beforeLoad();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _renderFilters()
    {
        $this->_filters = [];

        return parent::_renderFilters();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _renderOrders()
    {
        // Sort orders are managed through the search engine and are added through the prepareRequest method.
        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        // Resort items according the search response.
        $originalItems = $this->_items;
        $this->_items  = [];

        foreach ($this->queryResponse->getIterator() as $document) {
            $documentId = $document->getId();
            if (isset($originalItems[$documentId])) {
                $originalItems[$documentId]->setDocumentScore($document->getScore());
                $originalItems[$documentId]->setDocumentSource($document->getSource());
                $this->_items[$documentId] = $originalItems[$documentId];
            }
        }

        if (false === $this->_pageSize && false !== $this->originalPageSize) {
            $this->_pageSize = $this->originalPageSize;
        }

        return parent::_afterLoad();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * Prepares min and max price using Elasticsearch metric aggregation.
     * Ensures compatibility with Magento Core's getMinPrice()/getMaxPrice().
     *
     * @return self
     */
    protected function _prepareStatisticsData()
    {
        $storeId = $this->getStoreId();
        $requestName = $this->searchRequestName;
        $customerGroupId = (int) ($this->_productLimitationFilters['customer_group_id'] ?? CustomerGroup::NOT_LOGGED_IN_ID);
        $aggregationName = 'collection_price_stats';

        $facets = [
            'name'       => $aggregationName,
            'type'       => BucketInterface::TYPE_METRIC,
            'field'      => 'price.price',
            'metricType' => MetricInterface::TYPE_STATS,
            'nestedFilter' => ['price.customer_group_id' => $customerGroupId],
        ];

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $requestName,
            0,
            0,
            $this->query,
            [],
            $this->filters,
            $this->queryFilters,
            [$facets],
        );

        $response     = $this->searchEngine->search($searchRequest);
        $aggregations = $response->getAggregations();

        $bucket = $aggregations->getBucket($aggregationName);
        $metrics = current($bucket->getValues())->getMetrics();

        $rate = $this->getCurrencyRate();

        $this->_pricesCount            = (int) ($metrics['count'] ?? 0);
        $this->_minPrice               = round(((float) ($metrics['min'] ?? 0)) * $rate, 2);
        $this->_maxPrice               = round(((float) ($metrics['max'] ?? 0)) * $rate, 2);
        $this->_priceStandardDeviation = round(((float) ($metrics['std_deviation'] ?? 0)) * $rate, 2);

        return $this;
    }

    /**
     * Load product count :
     *  - collection size
     *  - number of products by attribute set (legacy)
     *  - number of products by attribute code
     *
     * @return void
     */
    private function loadProductCounts(): void
    {
        $storeId     = $this->getStoreId();
        $requestName = $this->searchRequestName;
        $facets = [
            ['name' => 'attribute_set_id', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
            ['name' => 'indexed_attributes', 'type' => BucketInterface::TYPE_TERM, 'size' => 0],
        ];
        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $requestName,
            0,
            0,
            $this->query,
            [],
            $this->filters,
            $this->queryFilters,
            $facets
        );
        $searchResponse = $this->searchEngine->search($searchRequest);
        $this->_totalRecords        = $searchResponse->count();
        $this->countByAttributeSet  = [];
        $this->countByAttributeCode = [];
        $this->isSpellchecked       = $searchRequest->isSpellchecked();
        $attributeSetIdBucket = $searchResponse->getAggregations()->getBucket('attribute_set_id');
        $attributeCodeBucket  = $searchResponse->getAggregations()->getBucket('indexed_attributes');
        if ($attributeSetIdBucket) {
            foreach ($attributeSetIdBucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $this->countByAttributeSet[$value->getValue()] = $metrics['count'];
            }
        }
        if ($attributeCodeBucket) {
            foreach ($attributeCodeBucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $this->countByAttributeCode[$value->getValue()] = $metrics['count'];
            }
        }
    }

    /**
     * Prepare the search request before it will be executed.
     *
     * @return RequestInterface
     */
    private function prepareRequest()
    {
        // Store id and request name.
        $storeId           = $this->getStoreId();
        $searchRequestName = $this->searchRequestName;

        // Pagination params.
        $size = $this->getPageSize();
        $from = $size * (max(1, $this->getCurPage()) - 1);

        // Setup sort orders.
        $sortOrders = $this->prepareSortOrders();

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $searchRequestName,
            $from,
            $size,
            $this->query,
            $sortOrders,
            $this->filters,
            $this->queryFilters,
            $this->facets
        );

        return $searchRequest;
    }

    /**
     * Prepare sort orders for the request builder.
     *
     * @return array()
     */
    private function prepareSortOrders()
    {
        $sortOrders = [];

        $useProductLimitation = isset($this->_productLimitationFilters['sortParams']);

        foreach ($this->_orders as $attribute => $direction) {
            $sortParams = ['direction' => $direction];
            $sortField  = $this->mapFieldName($attribute);

            if ($useProductLimitation && isset($this->_productLimitationFilters['sortParams'][$attribute])) {
                $sortField  = $this->_productLimitationFilters['sortParams'][$attribute]['sortField'];
                $sortParams = array_merge($sortParams, $this->_productLimitationFilters['sortParams'][$attribute]);
            } elseif ($attribute == 'price') {
                // Change the price sort field to the nested price field.
                $sortField = 'price.price';
                $sortParams['nestedPath'] = 'price';
                // Ensure we sort on the position field of the current customer group.
                $customerGroupId = $this->_productLimitationFilters['customer_group_id'];
                $sortParams['nestedFilter'] = ['price.customer_group_id' => $customerGroupId];
            }

            $sortOrders[$sortField] = $sortParams;
        }

        return $sortOrders;
    }

    /**
     * Convert standard field name to ES fieldname.
     * (eg. category_ids => category.category_id).
     *
     * @param string $fieldName Field name to be mapped.
     *
     * @return string
     */
    private function mapFieldName($fieldName)
    {
        return $this->requestFieldMapper->getMappedFieldName($fieldName);
    }
}
