<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Fulltext;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;

/**
 * Search engine category collection for Autocomplete.
 * Basically a copy-pasted version of @see Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
 *
 * @codingStandardsIgnoreStart
 * @TODO Refactor/Mutualize all copy/pasted methods.
 * @codingStandardsIgnoreEnd
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Category\Collection
{
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
     * @var QueryResponse
     */
    private $queryResponse;

    /**
     * @var string|QueryInterface
     */
    private $query;

    /**
     * @var boolean
     */
    private $isSpellchecked = false;

    /**
     * Collection constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory     The Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger            The Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy     Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager      Event Manager
     * @param \Magento\Eav\Model\Config                                    $eavConfig         EAV Configuration
     * @param \Magento\Framework\App\ResourceConnection                    $resource          Resource Connection
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory  Entity Factory
     * @param \Magento\Eav\Model\ResourceModel\Helper                      $resourceHelper    Resource Helper
     * @param \Magento\Framework\Validator\UniversalFactory                $universalFactory  Universal Factory
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager      Store Manager
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder               $requestBuilder    Search request builder.
     * @param \Magento\Search\Model\SearchEngine                           $searchEngine      Search engine
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection        Db Connection.
     * @param string                                                       $searchRequestName Search request name.
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Search\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        string $searchRequestName = 'category_search_container'
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
            $this->loadItemCounts();
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
     * Set search query filter in the collection.
     *
     * @param string|QueryInterface $query Search query text.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    public function setSearchQuery($query)
    {
        $this->query = $query;

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
                $result[$value->getValue()] = $metrics['count'];
            }
        }

        return $result;
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
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritdoc}
     */
    protected function _renderFiltersBefore()
    {
        $searchRequest = $this->prepareRequest();

        $this->queryResponse = $this->searchEngine->search($searchRequest);

        // Update the item count.
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
        $this->_pageSize = false;

        $this->isSpellchecked = $searchRequest->isSpellchecked();

        parent::_renderFiltersBefore();
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
        $this->_items = [];

        foreach ($this->queryResponse->getIterator() as $document) {
            $documentId = $document->getId();
            if (isset($originalItems[$documentId])) {
                $originalItems[$documentId]->setDocumentScore($document->getScore());
                $originalItems[$documentId]->setDocumentSource($document->getSource());
                $this->_items[$documentId] = $originalItems[$documentId];
            }
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
        $size = $this->_pageSize ? $this->_pageSize : 20;
        $from = $size * (max(1, $this->_curPage) - 1);

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
            $this->queryFilters
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

        foreach ($this->_orders as $attribute => $direction) {
            $sortParams = ['direction' => $direction];
            $sortField = $attribute;
            $sortOrders[$sortField] = $sortParams;
        }

        return $sortOrders;
    }

    /**
     * Load items count :
     *  - collection size
     *
     * @return void
     */
    private function loadItemCounts()
    {
        $storeId     = $this->getStoreId();
        $requestName = $this->searchRequestName;

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $requestName,
            0,
            0,
            $this->query,
            [],
            $this->filters,
            $this->queryFilters
        );

        $searchResponse = $this->searchEngine->search($searchRequest);

        $this->_totalRecords = $searchResponse->count();
    }
}
