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

namespace Smile\ElasticsuiteTracker\Api;

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
     * Retrieve event from the queue.
     *
     * @param int $limit Max number of event to retrieve.
     *
     * @return array
     */
    public function getEvents($limit = null);
}
