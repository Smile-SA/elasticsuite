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

namespace Smile\ElasticsuiteTracker\Model;

use Smile\ElasticsuiteTracker\Api\EventQueueInterface;
use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Tracker log event queue.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class EventQueue implements EventQueueInterface
{
    /**
     * @var ResourceModel\EventQueue
     */
    private $resourceModel;

    /**
     *
     * @var EventProcessorInterface
     */
    private $processors;

    /**
     * Constructor.
     *
     * @param ResourceModel\EventQueue $resourceModel   Resource model.
     * @param EventProcessorInterface  $eventProcessors Event processors.
     */
    public function __construct(ResourceModel\EventQueue $resourceModel, $eventProcessors = [])
    {
        $this->resourceModel = $resourceModel;
        $this->processors    = $eventProcessors;
    }

    /**
     * {@inheritDoc}
     */
    public function addEvent($eventData)
    {
        foreach ($this->processors as $processor) {
            $eventData = $processor->process($eventData);
        }

        $this->resourceModel->saveEvent($eventData);
    }

    /**
     * {@inheritDoc}
     */
    public function getEvents($limit = null)
    {
        return $this->resourceModel->getEvents($limit);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteEvents($eventIds)
    {
        $this->resourceModel->deleteEvents($eventIds);
    }

    /**
     * {@inheritDoc}
     */
    public function flagInvalidEvents($eventIds)
    {
        $this->resourceModel->flagInvalidEvents($eventIds);
    }

    /**
     * {@inheritDoc}
     */
    public function getInvalidEventsCount()
    {
        return $this->resourceModel->getInvalidEventsCount();
    }

    /**
     * {@inheritDoc}
     */
    public function purgeInvalidEvents($delay = 3, $limit = null)
    {
        $this->resourceModel->purgeInvalidEvents($delay, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function getPendingEventsCount($hours = 24)
    {
        return $this->resourceModel->getPendingEventsCount($hours);
    }
}
