<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Cron;

use Smile\ElasticsuiteTracker\Api\EventQueueInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;

/**
 * Cleanup invalid events from queue cronjob.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CleanupInvalidEvent
{
    /** @var EventQueueInterface */
    private $eventQueue;

    /** @var TrackerHelper */
    private $helper;

    /** @var integer */
    private $chunkSize;

    /**
     * Constructor.
     *
     * @param EventQueueInterface $eventQueue Event queue.
     * @param TrackerHelper       $helper     Tracker helper.
     * @param int                 $chunkSize  Invalid events deletion chunk size.
     */
    public function __construct(
        EventQueueInterface $eventQueue,
        TrackerHelper $helper,
        $chunkSize = 10000
    ) {
        $this->eventQueue = $eventQueue;
        $this->helper = $helper;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Purge from the event queue up to chunkSize invalid events older than defined by configuration.
     *
     * @return void
     */
    public function execute()
    {
        if ($this->eventQueue->getInvalidEventsCount() > 0) {
            $this->eventQueue->purgeInvalidEvents(
                $this->helper->getEventsQueueCleanupDelay(),
                $this->chunkSize
            );
        }
    }
}
