<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\ResourceModel\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * This class provides a lot of util methods used by Eav indexer related resource models.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
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
     * Constructor.
     *
     * @param ResourceConnection    $resource     Database adpater.
     * @param StoreManagerInterface $storeManager Store manager.
     */
    public function __construct(ResourceConnection $resource, StoreManagerInterface $storeManager)
    {
        $this->resource     = $resource;
        $this->connection   = $resource->getConnection();
        $this->storeManager = $storeManager;
    }


    /**
     * Get table name using the adapter.
     *
     * @param string $tableName Table name.
     *
     * @return string
     */
    protected function getTable($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Return database connection.
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get store by id.
     *
     * @param integer $storeId Store id.
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    protected function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }
}
