<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext;

use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;

/**
 * Search engine product collection.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
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
     * @var string
     */
    private $queryText;

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
     * @var array
     */
    private $countByAttributeSet;

    /**
     * @var array
     */
    private $fieldNameMapping = [
        'price'        => 'price.price',
        'position'     => 'category.position',
        'category_ids' => 'category.category_id',
    ];

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
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
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

        $this->requestBuilder    = $requestBuilder;
        $this->searchEngine      = $searchEngine;
        $this->searchRequestName = $searchRequestName;
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
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        $this->_orders[$attribute] = $dir;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $field = $this->mapFieldName($field);
        $this->filters[$field] = $condition;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        return $this->setOrder($attribute, $dir);
    }

    /**
     * Append a prebuilt (QueryInterface) query filter to the collection.
     *
     * @param QueryInterface $queryFilter Query filter.
     *
     * @return $this
     */
    public function addQueryFilter(QueryInterface $queryFilter)
    {
        $this->queryFilters[] = $queryFilter;

        return $this;
    }

    /**
     * Add search query filter
     *
     * @param string $query Search query text.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addSearchFilter($query)
    {
        $this->queryText = $query;

        return $this;
    }

    /**
     * Append a facet to the collection
     *
     * @param string $field       Facet field.
     * @param string $facetType   Facet type.
     * @param array  $facetConfig Facet config params.
     * @param array  $facetFilter Facet filter.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function addFacet($field, $facetType, $facetConfig, $facetFilter = null)
    {
        $this->facets[$field] = ['type' => $facetType, 'filter' => $facetFilter, 'config' => $facetConfig];

        return $this;
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
                $result[$metrics['value']] = $metrics;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function addCategoryFilter(\Magento\Catalog\Model\Category $category)
    {
        $categoryId = $category;

        if (is_object($category)) {
            $categoryId = $category->getId();
        }

        $this->addFieldToFilter('category_ids', $categoryId);
        $this->_productLimitationFilters['category_ids'] = $categoryId;

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
     * Load the product count by attribute set id.
     *
     * @return array
     */
    public function getProductCountByAttributeSetId()
    {
        if ($this->countByAttributeSet === null) {
            $this->loadProductCounts();
        }

        return $this->countByAttributeSet;
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

        $this->getSelect()->where('e.entity_id IN (?)', ['in' => $docIds]);
        $this->originalPageSize = $this->_pageSize;
        $this->_pageSize = false;

        $this->isSpellchecked = $searchRequest->isSpellchecked();

        return parent::_renderFiltersBefore();
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
        $orginalItems = $this->_items;
        $this->_items = [];

        foreach ($this->queryResponse->getIterator() as $document) {
            $documentId = $document->getId();
            if (isset($orginalItems[$documentId])) {
                $orginalItems[$documentId]->setDocumentScore($document->getScore());
                $orginalItems[$documentId]->setDocumentSource($document->getSource());
                $this->_items[$documentId] = $orginalItems[$documentId];
            }
        }

        if (false === $this->_pageSize && false !== $this->originalPageSize) {
            $this->_pageSize = $this->originalPageSize;
        }

        return parent::_afterLoad();
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
        $size = $this->_pageSize ? $this->_pageSize : $this->getSize();
        $from = $size * (max(1, $this->getCurPage()) - 1);

        // Query text.
        $queryText = $this->queryText;

        // Setup sort orders.
        $sortOrders = $this->prepareSortOrders();

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $searchRequestName,
            $from,
            $size,
            $queryText,
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

        $useProductuctLimitation = isset($this->_productLimitationFilters['sortParams']);

        foreach ($this->_orders as $attribute => $direction) {
            $sortParams = ['direction' => $direction];
            $sortField  = $this->mapFieldName($attribute);

            if ($useProductuctLimitation && isset($this->_productLimitationFilters['sortParams'][$attribute])) {
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
        if (isset($this->fieldNameMapping[$fieldName])) {
            $fieldName = $this->fieldNameMapping[$fieldName];
        }

        return $fieldName;
    }

    /**
     * Load product count :
     *  - collection size
     *  - number of products by attribute set
     *
     * @return void
     */
    private function loadProductCounts()
    {
        $storeId     = $this->getStoreId();
        $requestName = $this->searchRequestName;

        // Query text.
        $queryText = $this->queryText;

        $setIdFacet = ['attribute_set_id' => ['type' => BucketInterface::TYPE_TERM, 'config' => ['size' => 0]]];

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $requestName,
            0,
            0,
            $queryText,
            [],
            $this->filters,
            $this->queryFilters,
            $setIdFacet
        );

        $searchResponse = $this->searchEngine->search($searchRequest);

        $this->_totalRecords       = $searchResponse->count();
        $this->countByAttributeSet = [];
        $this->isSpellchecked = $searchRequest->isSpellchecked();

        $bucket = $searchResponse->getAggregations()->getBucket('attribute_set_id');

        if ($bucket) {
            foreach ($bucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $this->countByAttributeSet[$metrics['value']] = $metrics['count'];
            }
        }
    }
}
