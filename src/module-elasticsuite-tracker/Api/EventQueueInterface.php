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

namespace Smile\ElasticsuiteTracker\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Event log queue : store event to be indexed into ES.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface EventQueueInterface
{
    /**
     * Add an event to the queue.
     *
     * @param array $eventData Event data.
     *
     * @return void
     */
    public function addEvent($eventData);

    /**
     * Delete event by id.
     *
     * @param int[] $eventIds Event ids to delete.
     *
     * @return void
     */
    public function deleteEvents($eventIds);

    /**
     * Flag invalid events by their ids.
     *
     * @param int[] $eventsIds Event ids of events to flag as invalid.
     *
     * @return void
     */
    public function flagInvalidEvents($eventsIds);

    /**
     * Return the number of events flagged as invalid in the queue.
     *
     * @return int
     * @throws LocalizedException
     */
    public function getInvalidEventsCount();

    /**
     * Purge up to <limit> invalid events older than <delay> days from the queue.
     *
     * @param int      $delay Only invalid events older in <delay> days will be purged.
     * @param int|null $limit Max number of invalid events to purge at once.
     *
     * @return void
     */
    public function purgeInvalidEvents($delay = 3, $limit = null);

    /**
     * Retrieve event from the queue.
     *
     * @param int $limit Max number of event to retrieve.
     *
     * @return array
     */
    public function getEvents($limit = null);

    /**
     * Get the number of supposedly valid events not yet indexed after the specified amount of hours.
     *
     * @param int $hours Only events whose creation date is older than this amount of hours will be counted.
     *
     * @return int
     * @throws LocalizedException
     */
    public function getPendingEventsCount($hours = 24);
}
