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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

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

    /** @var DateTime */
    private $dateTime;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        Context.
     * @param \Magento\Framework\Serialize\Serializer\Json      $jsonSerializer JSON serializer.
     * @param DateTime                                          $dateTime       Date conversion model.
     * @param string                                            $connectionName DB connection name.
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);

        $this->jsonSerializer = $jsonSerializer;
        $this->dateTime = $dateTime;
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
            } catch (\InvalidArgumentException $exception) {
                $currentEventData = [];
            }
            $currentEventData['is_invalid'] = $this->isEventInvalid($currentEventData);
            $currentEvent = array_merge($currentEvent, $currentEventData);
            unset($currentEvent['data']);
        }

        return $eventData;
    }

    /**
     * Return the number of invalid events count.
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInvalidEventsCount()
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            ['count' => 'COUNT(*)']
        );
        $select->where('is_invalid = 1');

        return (int) $this->getConnection()->fetchOne($select);
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
     * Purge up to <limit> invalid events older than <delay> days from the queue.
     *
     * @param int      $delay Only invalid events older in <delay> days will be purged.
     * @param int|null $limit Max number of invalid events to purge at once.
     *
     * @return void
     * @throws LocalizedException
     */
    public function purgeInvalidEvents($delay = 3, $limit = null)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('is_invalid = ?', 1);

        if (!empty($limit)) {
            $select->limit($limit);
        }

        if ($delay > 0) {
            $select->where(
                'created_at <= ?',
                $connection->getDateSubSql(
                    $connection->quote($this->dateTime->formatDate(true)),
                    $delay,
                    AdapterInterface::INTERVAL_DAY
                )
            );
        }

        // Native deleteFromSelect+limit is broken with MariadDB.
        $select->reset(Select::DISTINCT);
        $select->reset(Select::COLUMNS);
        $query = sprintf('DELETE %s', $select->assemble());

        $connection->query($query);
    }

    /**
     * Get the number of supposedly valid events not yet indexed after the specified amount of hours.
     *
     * @param int $hours Only events whose creation date is older than this amount of hours will be counted.
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPendingEventsCount($hours = 24)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['count' => 'COUNT(*)'])
            ->where('is_invalid = ?', 0);

        if ($hours > 0) {
            $select->where(
                'created_at <= ?',
                $connection->getDateSubSql(
                    $connection->quote($this->dateTime->formatDate(true)),
                    $hours,
                    AdapterInterface::INTERVAL_HOUR
                )
            );
        }

        return (int) $this->getConnection()->fetchOne($select);
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function isEventInvalid($data)
    {
        $isEventInvalid = true;

        if (isset($data['page']['store_id']) && is_numeric($data['page']['store_id'])) {
            if (array_key_exists('session', $data)) {
                if (array_key_exists('uid', $data['session']) && array_key_exists('vid', $data['session'])) {
                    $isEventInvalid = false;
                    $sessionUid = trim($data['session']['uid'] ?? '');
                    $sessionVid = trim($data['session']['vid'] ?? '');
                    if (empty($sessionUid) || ("null" === $sessionUid)) {
                        $isEventInvalid = true;
                    }
                    if (empty($sessionVid) || ("null" === $sessionVid)) {
                        $isEventInvalid = true;
                    }
                }
            }
        }

        return $isEventInvalid;
    }
}
