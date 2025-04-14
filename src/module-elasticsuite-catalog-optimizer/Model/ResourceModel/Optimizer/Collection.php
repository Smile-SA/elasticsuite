<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer;

use Magento\Store\Model\Store;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Optimizers Collection Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $date;

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface    $entityFactory Entity factory.
     * @param \Psr\Log\LoggerInterface                                     $logger        Logger.
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy Fetch strategy.
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager  Event manager.
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                  $date          Date.
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection    Connection.
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource      Resource.
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);

        $this->date = $date;
    }

    /**
     * Set Store ID for filter
     *
     * @param Store|int $store The store.
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
     * Retrieve Store ID Filter.
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
            $searchContainers = [$searchContainers];
        }

        $cond = OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER. '.'
            . OptimizerInterface::OPTIMIZER_ID
            .' =  main_table.'
            . OptimizerInterface::OPTIMIZER_ID;

        $this->join(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER, $cond);
        $this->addFilter(OptimizerInterface::SEARCH_CONTAINER, ['in' => $searchContainers]);

        return $this;
    }

    /**
     * Returns only active optimizers.
     *
     * @param string $date Date the filter need to be active (UTC).
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection
     */
    public function addIsActiveFilter($date = null)
    {
        $this->addFieldToFilter('is_active', true);

        if ($date == null) {
            $date = $this->date->date('Y-m-d');
        }

        $this->getSelect()
            ->where('from_date is null or from_date <= ?', $date)
            ->where('to_date is null or to_date >= ?', $date);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(
            'Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer',
            'Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer'
        );
    }
}
