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

namespace Smile\ElasticsuiteTracker\Cron;

/**
 * Cron task used to index tracker log event.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexLogEvent
{
    /**
     * @var \Smile\ElasticsuiteTracker\Api\EventQueueInterface
     */
    private $eventQueue;

    /**
     * @var \Smile\ElasticsuiteTracker\Api\EventIndexInterface
     */
    private $eventIndex;

    /**
     * @var \Smile\ElasticsuiteTracker\Api\SessionIndexInterface
     */
    private $sessionIndex;

    /**
     * @var integer
     */
    private $chunkSize;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface   $eventQueue   Pending events queue.
     * @param \Smile\ElasticsuiteTracker\Api\EventIndexInterface   $eventIndex   Event index.
     * @param \Smile\ElasticsuiteTracker\Api\SessionIndexInterface $sessionIndex Session index.
     * @param integer                                              $chunkSize    Size of the chunk of events to index.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Smile\ElasticsuiteTracker\Api\EventIndexInterface $eventIndex,
        \Smile\ElasticsuiteTracker\Api\SessionIndexInterface $sessionIndex,
        $chunkSize = 10000
    ) {
        $this->eventQueue   = $eventQueue;
        $this->eventIndex   = $eventIndex;
        $this->sessionIndex = $sessionIndex;
        $this->chunkSize    = $chunkSize;
    }

    /**
     * Run the indexation.
     *
     * @return void
     */
    public function execute()
    {
        $events = $this->eventQueue->getEvents($this->chunkSize);
        if (!empty($events)) {
            $invalidEvents = array_filter(
                $events,
                function ($eventData) {
                    return (($eventData['is_invalid'] ?? true) === true);
                }
            );
            $events = array_diff_key($events, $invalidEvents);
            $this->eventIndex->indexEvents($events);
            $this->sessionIndex->indexEvents($events);
            $this->eventQueue->deleteEvents(array_keys($events));
            $this->eventQueue->flagInvalidEvents(array_keys($invalidEvents));
        }
    }
}
