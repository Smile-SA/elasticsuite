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
* @copyright 2016 Smile
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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue Pending events queue.
     * @param \Smile\ElasticsuiteTracker\Api\EventIndexInterface $eventIndex Event index.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\EventQueueInterface $eventQueue,
        \Smile\ElasticsuiteTracker\Api\EventIndexInterface $eventIndex
    ) {
        $this->eventQueue = $eventQueue;
        $this->eventIndex = $eventIndex;
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
            $this->eventQueue->deleteEvents(array_column($events, 'event_id'));
        }
    }
}
