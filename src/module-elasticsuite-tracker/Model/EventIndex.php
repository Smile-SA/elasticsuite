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

use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\Event\Mapping\Enforcer as MappingEnforcer;

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
     * @var MappingEnforcer
     */
    private $mappingEnforcer;

    /**
     * Constructor.
     *
     * @param IndexResolver                                             $indexResolver   Resource model.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation  Index operation.
     * @param MappingEnforcer                                           $mappingEnforcer Mapping enforcer.
     */
    public function __construct(
        IndexResolver $indexResolver,
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation,
        MappingEnforcer $mappingEnforcer
    ) {
        $this->indexResolver  = $indexResolver;
        $this->indexOperation = $indexOperation;
        $this->mappingEnforcer = $mappingEnforcer;
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
            if (isset($event['page']['store_id']) && is_numeric($event['page']['store_id'])) {
                // Previous "date" column has been renamed to "created_at" in db_schema.xml.
                if (!isset($event['date'])) {
                    $event['date'] = $event['created_at'];
                }

                $date = substr($event['date'], 0, 7);
                $index = $this->indexResolver->getIndex(self::INDEX_IDENTIFIER, $event['page']['store_id'], $date);
                if ($index !== null) {
                    $event = $this->mappingEnforcer->enforce($index, $event);
                    $indices[$index->getName()] = $index;
                    $bulk->addDocument($index, $event['event_id'], $event);
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
