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

namespace Smile\ElasticsuiteTracker\Model\ResourceModel;

use Smile\ElasticsuiteTracker\Api\EventIndexInterface;

/**
 * Event index resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class EventIndex
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface[]
     */
    private $indices;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexFactoryInterface
     */
    private $indexFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings Index settings.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory  $indexFactory  Index factory.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface       $client        ES client.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Smile\ElasticsuiteCore\Api\Index\IndexInterfaceFactory $indexFactory,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client
    ) {
        $this->client        = $client;
        $this->indexFactory  = $indexFactory;
        $this->indexSettings = $indexSettings;
    }

    /**
     * Get index using event data.
     *
     * @param array $event Event.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    public function getIndex($event)
    {
        $indexAlias      = $this->getIndexAlias($event);
        $indexName       = $this->getIndexName($event);
        $indexIdentifier = $this->getIndexIdenifier();

        if (!isset($this->indices[$indexName])) {
            $indexSettings = $this->indexSettings->getIndicesConfig();
            $indexConfig = array_merge(['identifier' => $indexAlias, 'name' => $indexName], $indexSettings[$indexIdentifier]);
            $this->indices[$indexName] = $this->indexFactory->create($indexConfig);
            $this->createIndexIfNotExists($this->indices[$indexName], $event['page']['store_id']);
        }

        return $this->indices[$indexName];
    }

    /**
     * Create the event index if not exists.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index Index.
     * @param int                                              $store Store id.
     *
     * @return void
     */
    private function createIndexIfNotExists(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index, $store)
    {
        if ($this->client->indexExists($index->getName()) === false) {
            $indexSettings = $this->indexSettings->getInstallIndexSettings();
            $indexSettings['analysis'] = $this->indexSettings->getAnalysisSettings($store);
            $this->client->createIndex($index->getName(), $indexSettings);
            $this->client->updateAliases([['add' => ['index' => $index->getName(), 'alias' => $index->getIdentifier()]]]);
            foreach ($index->getTypes() as $currentType) {
                $this->client->putMapping($index->getName(), $currentType->getName(), $currentType->getMapping()->asArray());
            }
        }
    }

    /**
     * Search index identifier.
     *
     * @return string
     */
    private function getIndexIdenifier()
    {
        return EventIndexInterface::INDEX_IDENTIFIER;
    }

    /**
     * Build index alias from an event.
     *
     * @param array $event Event.
     *
     * @return string
     */
    private function getIndexAlias($event)
    {
        $indexIdentifier = $this->getIndexIdenifier();

        return $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $event['page']['store_id']);
    }

    /**
     * Build index name from an event.
     *
     * @param arrat $event Event.
     *
     * @return string
     */
    private function getIndexName($event)
    {
        $indexAlias = $this->getIndexAlias($event);
        $date       = substr($event['date'], 0, 10);

        return sprintf("%s_%s", $indexAlias, str_replace("-", "", $date));
    }
}
