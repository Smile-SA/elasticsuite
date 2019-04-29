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
     * @var IndexResolver
     */
    private $indexResolver;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     * Constructor.
     *
     * @param IndexResolver                                             $indexResolver  Resource model.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation Index operation.
     */
    public function __construct(
        IndexResolver $indexResolver,
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation
    ) {
        $this->indexResolver  = $indexResolver;
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
        $bulk    = $this->indexOperation->createBulk();
        $indices = [];

        foreach ($events as $event) {
            if (isset($event['page']['store_id'])) {
                $index = $this->indexResolver->getIndex(self::INDEX_IDENTIFIER, $event['page']['store_id'], $event['date']);
                if ($index !== null) {
                    $indices[$index->getName()] = $index;
                    $bulk->addDocument($index, $index->getDefaultSearchType(), $event['event_id'], $event);
                }
            }
        }

        if ($bulk->isEmpty() === false) {
            $this->indexOperation->executeBulk($bulk);
        }

        foreach ($indices as $index) {
            $this->indexOperation->refreshIndex($index);
        }
    }
}
