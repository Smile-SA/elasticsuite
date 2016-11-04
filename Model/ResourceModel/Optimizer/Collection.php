<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer;

use Magento\Store\Model\Store;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Thesaurus Collection Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
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
     * Date
     *
     * @var date
     */
    private $date;

    /**
     * Init model for collection
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(
            'Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer',
            'Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer'
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
//        $this->loadSearchContainers();

        return parent::_afterLoad();
    }

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);

        $this->date = $date;
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
     * Add search containers filer.
     *
     * @param string $searchContainers Search containers.
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    public function addSearchContainersFilter($searchContainers)
    {
        if (!is_array($searchContainers)) {
            $searchContainers = array($searchContainers);
        }
        $this->addFilter(OptimizerInterface::SEARCH_CONTAINER, array('in' => $searchContainers));
        return $this;
    }


    /**
     * Filter collection by specified store ids
     *
     * @param array|int $searchContainers Search containers.
     *
     * @return $this
     */
//    public function addStoreFilter($searchContainers)
//    {
//        if (!is_array($searchContainers)) {
//            $searchContainers = [$searchContainers];
//        }
//
//        $this->getSelect()
//            ->join(
//                ['search_container_table' => $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER)],
//                'main_table.' . OptimizerInterface::OPTIMIZER_ID . ' = search_container_table.' . OptimizerInterface::OPTIMIZER_ID,
//                []
//            )
//            ->where('search_container_table.search_container IN (?)', $searchContainers)
//            ->group('main_table.' . OptimizerInterface::OPTIMIZER_ID);
//
//        return $this;
//    }

    /**
     * Returns only active optimizers.
     *
     * @param string|Zend_Date $date Date the filter need to be active (UTC).
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    public function addIsActiveFilter($date = null)
    {
        $this->addFieldToFilter('is_active', true);

        if (is_null($date)) {
            $date = $this->date->date('Y-m-d');
        }

        $this->getSelect()
            ->where('from_date is null or from_date <= ?', $date)
            ->where('to_date is null or to_date >= ?', $date);

        return $this;
    }

    /**
     * Perform operations after collection load
     *
     * @return array
     */
//    private function loadSearchContainers()
//    {
//        $select = $this->getConnection()->select();
//
//        $itemIds = array_keys($this->_items);
//
//        $select->from(['main' => $this->getTable(OptimizerInterface::TABLE_NAME)], [])
//            ->joinLeft(
//                ['search_container_table' => $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER)],
//                "main.".OptimizerInterface::OPTIMIZER_ID." = search_container_table.".OptimizerInterface::OPTIMIZER_ID,
//                []
//            )
//            ->where('main.'.OptimizerInterface::OPTIMIZER_ID.' IN (?)', $itemIds)
//            ->group(["main.".OptimizerInterface::OPTIMIZER_ID, "search_container_table.".OptimizerInterface::OPTIMIZER_ID])
//            ->columns(
//                [
//                    OptimizerInterface::OPTIMIZER_ID => 'main.'.OptimizerInterface::OPTIMIZER_ID,
//                ]
//            );
//
//        return $this->getConnection()->fetchAll($select);
//    }
}
