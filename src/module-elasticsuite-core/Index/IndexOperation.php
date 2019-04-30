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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;

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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Instanciate the index operation manager.
     *
     * @param \Magento\Framework\ObjectManagerInterface                $objectManager Object manager.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface       $client        ES client.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings ES settings.
     * @param \Psr\Log\LoggerInterface                                 $logger        Logger access.
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client,
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->objectManager        = $objectManager;
        $this->client               = $client;
        $this->indexSettings        = $indexSettings;
        $this->indicesConfiguration = $indexSettings->getIndicesConfig();
        $this->logger               = $logger;
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
        $indexSettings = ['settings' => $this->indexSettings->getCreateIndexSettings()];
        $indexSettings['settings']['analysis'] = $this->indexSettings->getAnalysisSettings($store);

        $this->client->createIndex($index->getName(), $indexSettings);

        foreach ($index->getTypes() as $currentType) {
            $this->client->putMapping($index->getName(), $currentType->getName(), $index->getMapping()->asArray());
        }


        return $index;
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
            $this->client->putIndexSettings($indexName, $this->indexSettings->getInstallIndexSettings());

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

        $bulkParams = ['body' => $bulk->getOperations()];

        $rawBulkResponse = $this->client->bulk($bulkParams);

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
                        "Bulk %s operation failed %d times in index %s for type %s.",
                        $error['operation'],
                        $error['count'],
                        $error['index'],
                        $error['document_type']
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
            }
        }

        return $bulkResponse;
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
