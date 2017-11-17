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

namespace Smile\ElasticsuiteTracker\Model;

use Smile\ElasticsuiteTracker\Api\EventIndexInterface;

/**
 * Event index implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class EventIndex implements EventIndexInterface
{
    /**
     * @var ResourceModel\EventIndex
     */
    private $resourceModel;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     *
     * @param ResourceModel\EventIndex                                  $resourceModel  Resource model.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation Index operation.
     */
    public function __construct(
        ResourceModel\EventIndex $resourceModel,
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation
    ) {
        $this->resourceModel  = $resourceModel;
        $this->indexOperation = $indexOperation;
    }

    /**
     * {@inheritDoc}
     */
    public function indexEvent($event)
    {
        $this->indexEvents([$event]);
    }

    /**
     * {@inheritDoc}
     */
    public function indexEvents($events)
    {
        $bulk = $this->indexOperation->createBulk();

        foreach ($events as $event) {
            $index = $this->resourceModel->getIndex($event);
            $bulk->addDocument($index, $index->getDefaultSearchType(), $event['event_id'], $event);
        }

        if ($bulk->isEmpty() === false) {
            $this->indexOperation->executeBulk($bulk);
        }
    }
}
