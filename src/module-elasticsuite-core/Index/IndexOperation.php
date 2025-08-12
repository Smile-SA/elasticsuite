<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface;
use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineManagerInterface;

/**
 * Default implementation of operation on indices (\Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface).
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexOperation implements IndexOperationInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexInterface[]
     */
    private $indicesByIdentifier = [];

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var array
     */
    private $indicesConfiguration;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineManagerInterface
     */
    private $pipelineManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Smile\ElasticsuiteCore\Model\Index\BulkError\Manager
     */
    private $bulkErrorManager;

    /**
     * Instantiate the index operation manager.
     *
     * @param \Magento\Framework\ObjectManagerInterface                $objectManager    Object manager.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface       $client           ES client.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings    ES settings.
     * @param PipelineManagerInterface                                 $pipelineManager  Ingest Pipeline Manager.
     * @param \Smile\ElasticsuiteCore\Model\Index\BulkError\Manager    $bulkErrorManager Bulk error manager.
     * @param \Psr\Log\LoggerInterface                                 $logger           Logger access.
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client,
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        PipelineManagerInterface $pipelineManager,
        \Smile\ElasticsuiteCore\Model\Index\BulkError\Manager $bulkErrorManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->objectManager        = $objectManager;
        $this->client               = $client;
        $this->indexSettings        = $indexSettings;
        $this->indicesConfiguration = $indexSettings->getIndicesConfig();
        $this->pipelineManager      = $pipelineManager;
        $this->logger               = $logger;
        $this->bulkErrorManager     = $bulkErrorManager;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable()
    {
        try {
            $isAvailable = $this->client->ping();
        } catch (\Exception $e) {
            $isAvailable = false;
        }

        return $isAvailable;
    }

    /**
     * {@inheritDoc}
     */
    public function indexExists($indexIdentifier, $store)
    {
        $exists = true;

        if (!isset($this->indicesByIdentifier[$indexIdentifier])) {
            $indexName = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
            $exists = $this->client->indexExists($indexName);
        }

        return $exists;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexByName($indexIdentifier, $store)
    {
        $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);

        if (!isset($this->indicesByIdentifier[$indexAlias])) {
            if (!$this->indexExists($indexIdentifier, $store)) {
                throw new \LogicException(
                    "{$indexIdentifier} index does not exist yet. Make sure everything is reindexed."
                );
            }
            $this->initIndex($indexIdentifier, $store, true);
        }

        return $this->indicesByIdentifier[$indexAlias];
    }

    /**
     * {@inheritDoc}
     */
    public function createIndex($indexIdentifier, $store)
    {
        $index         = $this->initIndex($indexIdentifier, $store, false);
        // @codingStandardsIgnoreStart
        $indexSettings = [
            'settings' => $this->indexSettings->getCreateIndexSettings($indexIdentifier) + $this->indexSettings->getDynamicIndexSettings($store),
        ];
        // @codingStandardsIgnoreEnd
        $indexSettings['settings']['analysis'] = $this->indexSettings->getAnalysisSettings($store);

        // Add (and create, if needed) default pipeline.
        $pipeline = $this->pipelineManager->createByIndexIdentifier($indexIdentifier);
        if ($pipeline !== null) {
            $indexSettings['settings']['default_pipeline'] = $pipeline->getName();
        }

        if ($index->useKnn()) {
            $indexSettings['settings']['index.knn'] = true;
        }

        $this->client->createIndex($index->getName(), $indexSettings);

        $this->client->putMapping($index->getName(), $index->getMapping()->asArray());

        $this->bulkErrorManager->cleanBulkErrors($store, $indexIdentifier);

        return $index;
    }

    /**
     * {@inheritDoc}
     */
    public function updateMapping($indexIdentifier, $store, $fields = [])
    {
        // Refresh indices configuration.
        $this->indicesConfiguration = $this->indexSettings->getIndicesConfig();
        try {
            $index = $this->getIndexByName($indexIdentifier, $store);
            // Mapping is injected in initIndex();.
            $mapping = $index->getMapping()->asArray();

            if (!empty($fields)) {
                $properties = $mapping['properties'] ?? [];
                if (!empty($properties) && is_array($properties)) {
                    $properties = array_filter(
                        $properties,
                        function ($key) use ($fields) {
                            return in_array($key, $fields);
                        },
                        ARRAY_FILTER_USE_KEY
                    );

                    $mapping['properties'] = $properties;
                }
            }

            $this->client->putMapping($index->getName(), $mapping);
        } catch (\LogicException $exception) {
            // Do nothing, we cannot update mapping of a non existing index.
        }
    }

    /**
     * {@inheritDoc}
     */
    public function installIndex(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index, $store)
    {
        if ($index->needInstall()) {
            $indexIdentifier = $index->getIdentifier();
            $indexName       = $index->getName();
            $indexAlias      = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);

            $this->client->forceMerge($indexName);
            $this->client->putIndexSettings($indexName, $this->indexSettings->getInstallIndexSettings($indexIdentifier));

            $this->proceedIndexInstall($indexName, $indexAlias);
        }

        return $index;
    }

    /**
     * {@inheritDoc}
     */
    public function createBulk()
    {
        return $this->objectManager->create('Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface');
    }

    /**
     * {@inheritDoc}
     */
    public function executeBulk(\Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk)
    {
        if ($bulk->isEmpty()) {
            throw new \LogicException('Can not execute empty bulk.');
        }

        $bulkParams      = ['body' => $bulk->getOperations()];
        $rawBulkResponse = $this->client->bulk($bulkParams);

        return $this->parseBulkResponse($rawBulkResponse);
    }

    /**
     * {@inheritDoc}
     */
    public function refreshIndex(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index)
    {
        try {
            $this->client->refreshIndex($index->getName());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBatchIndexingSize()
    {
        return $this->indexSettings->getBatchIndexingSize();
    }

    /**
     * {@inheritDoc}
     */
    public function proceedIndexInstall($indexName, $indexAlias)
    {
        $aliasActions   = [['add' => ['index' => $indexName, 'alias' => $indexAlias]]];
        $deletedIndices = [];

        $oldIndices = $this->client->getIndicesNameByAlias($indexAlias);

        foreach ($oldIndices as $oldIndexName) {
            if ($oldIndexName != $indexName) {
                $deletedIndices[] = $oldIndexName;
                $aliasActions[]   = ['remove' => ['index' => $oldIndexName, 'alias' => $indexAlias]];
            }
        }

        $this->client->updateAliases($aliasActions);

        foreach ($deletedIndices as $deletedIndex) {
            $this->client->deleteIndex($deletedIndex);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reindex(
        array $sourceIndices,
        string $destIndex,
        array $bodyParams = [],
        array $sourceParams = [],
        array $destParams = []
    ): array {
        $params = [
            'source' => array_merge(['index' => $sourceIndices], $sourceParams),
            'dest'   => array_merge(['index' => $destIndex], $destParams),
        ];
        $params = array_merge($params, $bodyParams);

        return $this->client->reindex(['body' => $params]);
    }

    /**
     * Compute and process a raw bulk response to a bulk response object.
     *
     * @param array $rawBulkResponse The raw bulk response from the client.
     *
     * @return BulkResponseInterface
     */
    protected function parseBulkResponse(array $rawBulkResponse)
    {
        /**
         * @var \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface
         */
        $bulkResponse = $this->objectManager->create(
            'Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface',
            ['rawResponse' => $rawBulkResponse]
        );

        if ($bulkResponse->hasErrors()) {
            foreach ($bulkResponse->aggregateErrorsByReason() as $error) {
                $sampleDocumentIds = implode(', ', array_slice($error['document_ids'], 0, 10));
                $errorMessages = [
                    sprintf(
                        "Bulk %s operation failed %d times in index %s.",
                        $error['operation'],
                        $error['count'],
                        $error['index']
                    ),
                    sprintf(
                        "Error (%s) : %s.",
                        $error['error']['type'],
                        $error['error']['reason']
                    ),
                    sprintf(
                        "Failed doc ids sample : %s.",
                        $sampleDocumentIds
                    ),
                ];
                $this->logger->error(implode(" ", $errorMessages));
                if ($error['operation'] === BulkRequestInterface::ACTION_INDEX) {
                    $this->bulkErrorManager->recordError(
                        $error['index'],
                        $error['operation'],
                        $error['error']['type'],
                        $error['error']['simple_reason'],
                        $error['error']['reason'],
                        (int) $error['count'] ?? 1,
                        implode(', ', $error['document_ids']),
                    );
                }
            }
        }

        return $bulkResponse;
    }

    /**
     * Init the index object.
     *
     * @param string                                                $indexIdentifier An index indentifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           The store.
     * @param boolean                                               $existingIndex   Is the index already existing.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    private function initIndex($indexIdentifier, $store, $existingIndex)
    {
        if (!isset($this->indicesConfiguration[$indexIdentifier])) {
            throw new \LogicException(
                "No index found with identifier {$indexIdentifier} into elasticsuite_indices.xml."
            );
        }

        $indexSettings    = $this->indexSettings;
        $indexAlias       = $indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
        $indexName        = $indexSettings->createIndexNameFromIdentifier($indexIdentifier, $store);
        $needInstall      = !$existingIndex;

        if ($existingIndex) {
            $indexName = $indexAlias;
        }

        $createIndexParams = ['identifier' => $indexIdentifier, 'name' => $indexName, 'needInstall' => $needInstall];

        $createIndexParams += $this->indicesConfiguration[$indexIdentifier];

        $index = $this->objectManager->create('\Smile\ElasticsuiteCore\Api\Index\IndexInterface', $createIndexParams);

        $this->indicesByIdentifier[$indexAlias] = $index;

        return $this->indicesByIdentifier[$indexAlias];
    }
}
