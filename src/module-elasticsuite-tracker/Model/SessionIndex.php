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

use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;

/**
 * Session index implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SessionIndex implements SessionIndexInterface
{
    /**
     * @var ResourceModel\SessionIndex
     */
    private $resourceModel;

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
     * @param IndexResolver                                             $indexResolver  Index resolver.
     * @param ResourceModel\SessionIndex                                $resourceModel  Resource model.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation Index operation.
     */
    public function __construct(
        IndexResolver $indexResolver,
        ResourceModel\SessionIndex $resourceModel,
        \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface $indexOperation
    ) {
        $this->resourceModel  = $resourceModel;
        $this->indexOperation = $indexOperation;
        $this->indexResolver  = $indexResolver;
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
        $sessionIdsByStore = $this->getSessionIdsByStore($events);
        $bulk              = $this->indexOperation->createBulk();
        $indices           = [];

        foreach ($sessionIdsByStore as $storeId => $sessionIds) {
            $sessionData = $this->resourceModel->getSessionData($storeId, $sessionIds);

            foreach ($sessionData as $session) {
                if (isset($session['store_id'])) {
                    $index = $this->indexResolver->getIndex(self::INDEX_IDENTIFIER, $session['store_id'], $session['start_date']);
                    if ($index !== null) {
                        $indices[$index->getName()] = $index;
                        $bulk->addDocument($index, $index->getDefaultSearchType(), $session['session_id'], $session);
                    }
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

    /**
     * Extract session ids from event by store.
     *
     * @param array $events Events
     *
     * @return array
     */
    private function getSessionIdsByStore($events)
    {
        $sessionIdsByStore = [];

        foreach ($events as $event) {
            if (isset($event['page']['store_id']) && isset($event['session']['uid'])) {
                $sessionIdsByStore[$event['page']['store_id']][] = $event['session']['uid'] ?? 0;
            }
        }

        return $sessionIdsByStore;
    }
}
