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
 * @copyright 2020 Smile
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
     * Get event from the queue while validating them.
     * Invalid events will be flagged as such.
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

        $select->where('is_invalid = 0');

        $eventData = $this->getConnection()->fetchAssoc($select);

        foreach ($eventData as &$currentEvent) {
            try {
                $currentEventData = $this->jsonSerializer->unserialize($currentEvent['data']);
                $currentEventData['is_invalid'] = $this->isEventInvalid($currentEventData);
            } catch (\InvalidArgumentException $exception) {
                $currentEventData = [];
            }
            $currentEvent = array_merge($currentEvent, $currentEventData);
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
     * Flag some events as invalid to prevent them from being read the next time the events queue is processed.
     *
     * @param array $eventIds Event ids to be flagged as invalid.
     *
     * @return void
     */
    public function flagInvalidEvents(array $eventIds)
    {
        if (!empty($eventIds)) {
            $connection = $this->getConnection();
            $connection->update(
                $this->getMainTable(),
                ['is_invalid' => 1],
                $connection->quoteInto('event_id IN(?)', $eventIds)
            );
        }
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

    /**
     * Returns true if the event is invalid and should not be indexed because it lacks some data,
     * return false otherwise.
     *
     * @param array $data Event data
     *
     * @return bool
     */
    protected function isEventInvalid($data)
    {
        $isEventInvalid = true;
        if (array_key_exists('session', $data)) {
            if (array_key_exists('uid', $data['session']) && array_key_exists('vid', $data['session'])) {
                $isEventInvalid = false;
            }
        }

        return $isEventInvalid;
    }
}
