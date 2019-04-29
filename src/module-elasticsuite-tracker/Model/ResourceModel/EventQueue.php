<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Tracker log event queue resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class EventQueue extends AbstractDb
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        Context.
     * @param \Magento\Framework\Serialize\Serializer\Json      $jsonSerializer JSON serializer.
     * @param string                                            $connectionName DB connection name.
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Save an event to the queue.
     *
     * @param array $eventData Event data.
     *
     * @return void
     */
    public function saveEvent($eventData)
    {
        $serializedEvent = $this->jsonSerializer->serialize($eventData);
        $insertData = ['event_id' => md5($serializedEvent . time()), 'data' => $serializedEvent];
        $this->getConnection()->insertOnDuplicate($this->getMainTable(), $insertData, ['data']);
    }

    /**
     * Get event from the queue.
     *
     * @param integer $limit Max number of events to be retrieved.
     *
     * @return array
     */
    public function getEvents($limit = null)
    {
        $select = $this->getConnection()->select()->from($this->getMainTable());

        if ($limit !== null) {
            $select->limit($limit);
        }

        $eventData = $this->getConnection()->fetchAll($select);

        foreach ($eventData as &$currentEvent) {
            $currentEvent = array_merge($currentEvent, $this->jsonSerializer->unserialize($currentEvent['data']));
            unset($currentEvent['data']);
        }

        return $eventData;
    }

    /**
     * Clean event from the reindex queue.
     *
     * @param array $eventIds Event ids to be deleted.
     *
     * @return void
     */
    public function deleteEvents(array $eventIds)
    {
        $connection = $this->getConnection();
        $connection->delete($this->getMainTable(), $connection->quoteInto('event_id IN(?)', $eventIds));
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init('elasticsuite_tracker_log_event', 'event_id');
    }
}
