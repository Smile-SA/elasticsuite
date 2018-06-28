<?php
/**
 * DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
* versions in the future.
*
* @category  Smile
* @package   Smile\ElasticsuiteTracker
* @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface   $eventQueue   Pending events queue.
     * @param \Smile\ElasticsuiteTracker\Api\SessionIndexInterface $eventIndex   Event index.
     * @param \Smile\ElasticsuiteTracker\Api\EventIndexInterface   $sessionIndex Session index.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Smile\ElasticsuiteTracker\Api\EventIndexInterface $eventIndex,
        \Smile\ElasticsuiteTracker\Api\SessionIndexInterface $sessionIndex
    ) {
        $this->eventQueue   = $eventQueue;
        $this->eventIndex   = $eventIndex;
        $this->sessionIndex = $sessionIndex;
    }

    /**
     * Run the indexation.
     *
     * @return void
     */
    public function execute()
    {
        $events = $this->eventQueue->getEvents();
        if (!empty($events)) {
            $this->eventIndex->indexEvents($events);
            $this->sessionIndex->indexEvents($events);
            $this->eventQueue->deleteEvents(array_column($events, 'event_id'));
        }
    }
}
