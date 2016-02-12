<?php

namespace Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class AbstractIndexer
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource, StoreManagerInterface $storeManager)
    {
        $this->resource     = $resource;
        $this->connection   = $resource->getConnection();
        $this->storeManager = $storeManager;
    }

    /**
     *
     * @return string
     */
    protected function getTable($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     *
     * @param int $storeId
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    protected function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     *
     * @param \Magento\Store\Api\Data\StoreInterface|int|string $store
     *
     * @return int
     */
    protected function getRootCategoryId($store)
    {
        if (is_numeric($store) || is_string($store)) {
            $store = $this->getStore($store);
        }

        $storeGroupId = $store->getStoreGroupId();

        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }
}