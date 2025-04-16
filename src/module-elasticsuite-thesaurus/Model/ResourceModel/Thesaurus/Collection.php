<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model\ResourceModel\Thesaurus;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus Collection Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
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
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection     Database Connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource       Abstract Resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Helper $resourceHelper,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
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
        $this->addTermFilterToSelect($term);

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
     */
    protected function _renderFiltersBefore()
    {
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
    protected function _construct()
    {
        $this->_init(
            'Smile\ElasticsuiteThesaurus\Model\Thesaurus',
            'Smile\ElasticsuiteThesaurus\Model\ResourceModel\Thesaurus'
        );
    }

    /**
     * Perform operations after collection load
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $this->loadStores();
        $this->loadTermsData();

        return parent::_afterLoad();
    }

    /**
     * Inject terms data on collection
     *
     * @param string $term Term filter text.
     *
     * @return $this
     */
    private function addTermFilterToSelect($term)
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

            $select->where("expansion_table.term LIKE '%{$term}%'", $term)
                ->orWhere("reference_table.term LIKE '%{$term}%'", $term);

            $this->termsLinked = true;
        }

        return $select;
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
                ->where('thesaurus_entity_store.' . ThesaurusInterface::THESAURUS_ID . ' IN (?)', $itemIds)
            ;

            $result = [];
            foreach ($connection->fetchAll($select) as $item) {
                $result[$item[ThesaurusInterface::THESAURUS_ID]][] = $item[ThesaurusInterface::STORE_ID];
            }

            if ($result) {
                foreach ($this as $item) {
                    $entityId = $item->getData(ThesaurusInterface::THESAURUS_ID);
                    if (!isset($result[$entityId])) {
                        continue;
                    }
                    $storeCodes = [];
                    $storeIds = [];
                    foreach ($result[$entityId] as $storeId) {
                        if ($storeId == 0) {
                            $storeIds = array_map(function (StoreInterface $store) {
                                return $store->getId();
                            }, $this->storeManager->getStores(false, true));
                            $storeCodes = array_map(function (StoreInterface $store) {
                                return $store->getCode();
                            }, $this->storeManager->getStores(false, true));
                        }
                        if ($storeId != 0) {
                            $storeCodes[] = $this->storeManager->getStore($storeId)->getCode();
                            $storeIds[] = $storeId;
                        }
                    }

                    $item->setData('store_ids', $storeIds);
                    $item->setData('store_codes', implode(";", $storeCodes));
                }
            }
        }
    }

    /**
     * Perform operations after collection load
     *
     * @return void
     */
    private function loadTermsData()
    {
        $select = $this->getConnection()->select();

        $itemIds = array_keys($this->_items);

        $select->from(['exp' => $this->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME)], [])
            ->joinLeft(
                ['ref' => $this->getTable(ThesaurusInterface::REFERENCE_TABLE_NAME)],
                "exp.thesaurus_id = ref.thesaurus_id AND exp.term_id = ref.term_id",
                []
            )
            ->where('exp.thesaurus_id IN (?)', $itemIds)
            ->group(["exp.thesaurus_id", "exp.term_id"])
            ->columns(
                [
                    'thesaurus_id'    => 'exp.thesaurus_id',
                    'expansions_terms' => new \Zend_Db_Expr("GROUP_CONCAT(exp.term)"),
                    'expanded_term'    => 'ref.term',
                ]
            );

        $data = $this->getConnection()->fetchAll($select);

        $this->addTermData($data);
        $this->addTermExportData($data);
    }

    /**
     * Process terms of each thesaurus and display a summary for each thesaurus.
     *
     * @param array $termData Raw terms data loaded from the DB.
     *
     * @return $this
     */
    private function addTermData($termData)
    {
        $labelsByThesaurusId = [];

        foreach ($termData as $currentTerm) {
            $label = $currentTerm['expansions_terms'];

            if (isset($currentTerm['expanded_term']) && $currentTerm['expanded_term']) {
                $label = sprintf("%s => %s", $currentTerm['expanded_term'], $label);
            }

            $labelsByThesaurusId[$currentTerm['thesaurus_id']][] = $label;
        }

        foreach ($labelsByThesaurusId as $thesaurusId => $labels) {
            $this->_items[$thesaurusId]->setData('terms_summary', implode(" <br/> ", $labels));
        }

        return $this;
    }

    /**
     * Process terms of each thesaurus for export.
     *
     * @param array $termData Raw terms data loaded from the DB.
     *
     * @return $this
     */
    private function addTermExportData($termData)
    {
        $labelsByThesaurusId = [];

        foreach ($termData as $currentTerm) {
            $label = $currentTerm['expansions_terms'];

            if (isset($currentTerm['expanded_term']) && $currentTerm['expanded_term']) {
                $label = sprintf("%s:%s", $currentTerm['expanded_term'], $label);
            }

            $labelsByThesaurusId[$currentTerm['thesaurus_id']][] = $label;
        }

        foreach ($labelsByThesaurusId as $thesaurusId => $labels) {
            $this->_items[$thesaurusId]->setData('terms_export', implode(";", $labels));
        }

        return $this;
    }
}
