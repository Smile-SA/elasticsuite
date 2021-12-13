<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\ResourceModel\Viewer;

use Magento\Framework\App\ResourceConnection;
use Smile\ElasticsuiteTracker\Model\Viewer\Log;
use Smile\ElasticsuiteTracker\Model\Viewer\LogFactory;

/**
 * Admin Analytics log data logger.
 * Saves and retrieves release notification viewer log data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Logger
{
    /**
     * Admin Analytics usage version log table name
     */
    const LOG_TABLE_NAME = 'smile_elasticsuite_notification_log';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * Logger constructor
     *
     * @param ResourceConnection $resource   Resource.
     * @param LogFactory         $logFactory Log factory.
     */
    public function __construct(
        ResourceConnection $resource,
        LogFactory $logFactory
    ) {
        $this->resource = $resource;
        $this->logFactory = $logFactory;
    }

    /**
     * Save (insert new or update existing) log.
     *
     * @param string $notificationCode Notification code.
     * @return bool
     */
    public function log(string $notificationCode): bool
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->insertOnDuplicate(
            $this->resource->getTableName(self::LOG_TABLE_NAME),
            ['notification_code' => $notificationCode],
            ['notification_code']
        );

        return true;
    }

    /**
     * Get log by the last view version.
     *
     * @param string $notificationCode Notification code.
     * @return Log
     */
    public function get(string $notificationCode): Log
    {
        return $this->logFactory->create(['data' => $this->loadLatestLogData($notificationCode)]);
    }

    /**
     * Checks is log already exists.
     *
     * @param string $notificationCode Notification code.
     * @return boolean
     */
    public function checkLogExists(string $notificationCode): bool
    {
        $data = $this->logFactory->create(['data' => $this->loadLatestLogData($notificationCode)]);
        $lastViewedVersion = $data->getNotificationCode();

        return isset($lastViewedVersion);
    }

    /**
     * Load release notification viewer log data by last view version
     *
     * @param string $notificationCode Notification code.
     * @return array
     */
    private function loadLatestLogData(string $notificationCode): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['log_table' => $this->resource->getTableName(self::LOG_TABLE_NAME)])
            ->where('log_table.notification_code = ?', $notificationCode)
            ->order('log_table.id desc')
            ->limit(['count' => 1]);

        $data = $connection->fetchRow($select);
        if (!$data) {
            $data = [];
        }

        return $data;
    }
}
