<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Model\ResourceModel\Thesaurus;

use Magento\Store\Model\Store;
use Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus Collection Resource Model
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Store for filter
     *
     * @var integer
     */
    private $storeId;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Search resource helper
     *
     * @var \Magento\Framework\DB\Helper
     */
    private $resourceHelper;

    /**
     * If link with terms has already been established
     *
     * @var boolean
     */
    private $termsLinked = false;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory  Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger         Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy  Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager   Event Manager
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager   Store Manager
     * @param \Magento\Framework\DB\Helper                                 $resourceHelper Resource Helper
     * @param \Magento\Framework\DB\Adapter\AdapterInterface               $connection     Database Connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource       Abstract Resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->storeManager = $storeManager;
        $this->resourceHelper = $resourceHelper;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Set Store ID for filter
     *
     * @param Store|int $store The store
     *
     * @return $this
     */
    public function setStoreId($store)
    {
        if ($store instanceof Store) {
            $store = $store->getId();
        }
        $this->storeId = $store;

        return $this;
    }

    /**
     * Retrieve Store ID Filter
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Set thesaurus term to filter
     *
     * @param string $term The term to filter
     *
     * @return $this
     */
    public function setTermFilter($term)
    {
        $term = preg_replace("/[\s-]/", "%", $term);

        $this->initSelectWithTerms();

        $this->getSelect()
            ->where("expansion_table.term LIKE '%{$term}%'", $term)
            ->orWhere("reference_table.term LIKE '%{$term}%'", $term);

        return $this;
    }

    /**
     * Filter collection by specified store ids
     *
     * @param array|int $storeIds The store ids to filter
     *
     * @return $this
     */
    public function addStoreFilter($storeIds)
    {
        $defaultStoreId = Store::DEFAULT_STORE_ID;

        if (!is_array($storeIds)) {
            $storeIds = [$storeIds];
        }
        if (!in_array($defaultStoreId, $storeIds)) {
            $storeIds[] = $defaultStoreId;
        }

        $this->getSelect()
            ->join(
                ['store_table' => $this->getTable(ThesaurusInterface::STORE_TABLE_NAME)],
                'main_table.' . ThesaurusInterface::THESAURUS_ID . ' = store_table.' . ThesaurusInterface::THESAURUS_ID,
                []
            )
            ->where('store_table.store_id IN (?)', $storeIds)
            ->group('main_table.' . ThesaurusInterface::THESAURUS_ID);

        return $this;
    }

    /**
     * Join store relation table if there is store filter
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _renderFiltersBefore()
    {
        //@codingStandardsIgnoreEnd
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                ['store_table' => $this->getTable(ThesaurusInterface::STORE_TABLE_NAME)],
                'main_table.' . ThesaurusInterface::THESAURUS_ID . ' = store_table.' . ThesaurusInterface::THESAURUS_ID,
                []
            )->group(
                'main_table.' . ThesaurusInterface::THESAURUS_ID
            );
        }

        parent::_renderFiltersBefore();
    }

    /**
     * Init model for collection
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _construct()
    {
        //@codingStandardsIgnoreEnd
        $this->_init(
            'Smile\ElasticSuiteThesaurus\Model\Thesaurus',
            'Smile\ElasticSuiteThesaurus\Model\ResourceModel\Thesaurus'
        );
    }

    /**
     * Perform operations after collection load
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return $this
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _afterLoad()
    {
        //@codingStandardsIgnoreEnd
        $this->loadStores();
        $this->injectTermsData();

        return parent::_afterLoad();
    }

    /**
     * Inject terms data on collection
     *
     * @return $this
     */
    private function initSelectWithTerms()
    {
        $select = $this->getSelect();

        if ($this->termsLinked === false) {
            $select->joinLeft(
                ['expansion_table' => $this->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME)],
                new \Zend_Db_Expr("main_table.thesaurus_id = expansion_table.thesaurus_id"),
                [
                    'expansion_terms' => new \Zend_Db_Expr("GROUP_CONCAT( DISTINCT expansion_table.term SEPARATOR ',')"),
                ]
            );

            $select->joinLeft(
                ['reference_table' => $this->getTable(ThesaurusInterface::REFERENCE_TABLE_NAME)],
                new \Zend_Db_Expr(
                    "reference_table.term_id = expansion_table.term_id " .
                    "AND main_table." . ThesaurusInterface::THESAURUS_ID . " = reference_table." . ThesaurusInterface::THESAURUS_ID
                ),
                ['reference_terms' => new \Zend_Db_Expr("GROUP_CONCAT( DISTINCT reference_table.term SEPARATOR ',')")]
            );

            $select->group("main_table." . ThesaurusInterface::THESAURUS_ID);
            $this->termsLinked = true;
        }

        return $select;
    }

    /**
     * Perform operations after collection load
     *
     * @return void
     */
    private function injectTermsData()
    {
        $select    = $this->initSelectWithTerms();
        $termsData = $this->getConnection()->fetchAssoc($select);
        $this->appendTermsSummary($termsData);
    }

    /**
     * Perform operations after collection load
     *
     * @return void
     */
    private function loadStores()
    {
        $itemIds = array_keys($this->_items);

        if (count($itemIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from(['thesaurus_entity_store' => $this->getTable(ThesaurusInterface::STORE_TABLE_NAME)])
                ->where('thesaurus_entity_store.' . ThesaurusInterface::THESAURUS_ID . ' IN (?)', $itemIds);

            $result = $connection->fetchPairs($select);

            if ($result) {
                foreach ($this as $item) {
                    $entityId = $item->getData(ThesaurusInterface::THESAURUS_ID);
                    if (!isset($result[$entityId])) {
                        continue;
                    }
                    $storeId = $result[$item->getData(ThesaurusInterface::THESAURUS_ID)];
                    $storeCode = $this->storeManager->getStore($storeId)->getCode();

                    if ($result[$entityId] == 0) {
                        $stores = $this->storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                        $storeCode = key($stores);
                    }

                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_code', $storeCode);
                    $item->setData('store_id', [$result[$entityId]]);
                }
            }
        }
    }

    /**
     * Append terms summary to items
     *
     * @param array $termsData All terms data for items
     */
    private function appendTermsSummary($termsData)
    {
        if (count($termsData)) {
            foreach ($this as $item) {
                $entityId = $item->getId();
                if (!isset($termsData[$entityId])) {
                    continue;
                }
                $concatenatedTerms = '';
                if (trim($termsData[$entityId]['reference_terms']) != '') {
                    $termsLabel = $termsData[$entityId]['reference_terms'];
                    $concatenatedTerms .= "[" . $termsLabel . "] => ";
                }
                if ($termsData[$entityId]['expansion_terms'] !== '') {
                    $termsLabel = $termsData[$entityId]['expansion_terms'];
                    $concatenatedTerms .= $termsLabel;
                }

                $item->setData('terms_summary', $concatenatedTerms);
            }
        }
    }
}
