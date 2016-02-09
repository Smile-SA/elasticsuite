<?php

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;

class IndexOperation implements IndexOperationInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var IndexInterface[]
     */
    private $indicesByIdentifier = [];

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var array
     */
    private $indicesConfiguration;

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ClientFactoryInterface $clientFactory,
        IndexSettingsInterface $indexSettings
    )
    {
        $this->objectManager        = $objectManager;
        $this->client               = $clientFactory->createClient();
        $this->indexSettings        = $indexSettings;
        $this->indicesConfiguration = $indexSettings->getIndicesConfig();
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::isAvailable()
     */
    public function isAvailable()
    {
        try {
            $isAvailable = $this->client->ping();
        } catch (\Exception $e){
            $isAvailable = false;
        }
        return $isAvailable;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::indexExists()
     */
    public function indexExists($indexIdentifier, $store)
    {
        $exists = true;
        if (!isset($this->indices[$indexIdentifier])) {
            $indexName = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
            $exists = $this->client->indices()->exists(['index' => $indexName]);
        }
        return $exists;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::getIndexByName()
     */
    public function getIndexByName($indexIdentifier, $store)
    {
        $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
        if (!isset($this->indices[$indexAlias])) {
            if ($this->indexExists($indexIdentifier, $store)) {
                $this->initIndex($indexIdentifier, $store, true);
            } else {
                throw new \LogicException("{$indexIdentifier} index does not exist yet. Make sure evything is reindexed");
            }
        }
        return $this->indices[$indexAlias];
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::createIndex()
     */
    public function createIndex($indexIdentifier, $store)
    {
        $index         = $this->initIndex($indexIdentifier, $store, false);
        $indexSettings = ['settings' => $this->indexSettings->getCreateIndexSettings()];
        $indexSettings['settings']['analysis'] = $this->indexSettings->getAnalysisSettings($store);

        foreach ($index->getTypes() as $currentType) {
            $indexSettings['mappings'][$currentType->getName()] = $currentType->getMapping()->asArray();
        }

        $this->client->indices()->create(['index' => $index->getName(), 'body' => $indexSettings]);
        return $index;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::installIndex()
     */
    public function installIndex(IndexInterface $index, $store)
    {
        $indexIdentifier = $index->getIdentifier();
        $indexName       = $index->getName();
        $indexAlias      = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);

        $this->client->indices()->optimize(['index' => $indexName]);
        $this->client->indices()->putSettings(['index' => $indexName, 'body' => $this->indexSettings->getInstallIndexSettings()]);

        $this->proceedIndexInstall($indexName, $indexAlias);

        return $index;
    }

    private function proceedIndexInstall($indexName, $indexAlias)
    {
        $aliasActions   = [['add' => ['index' => $indexName, 'alias' => $indexAlias]]];
        $deletedIndices = [];

        try {
            $oldIndices = $this->client->indices()->getMapping(['index' => $indexAlias]);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            $oldIndices = array();
        }

        foreach (array_keys($oldIndices) as $oldIndexName) {
            if ($oldIndexName != $indexName) {
                $deletedIndices[] = $oldIndexName;
                $aliasActions[] = ['remove' => ['index' => $oldIndexName, 'alias' => $indexAlias]];
            }
        }

        $this->client->indices()->updateAliases(['body' => ['actions' => $aliasActions]]);

        foreach ($deletedIndices as $deletedIndex) {
            // @todo : Dispatch event
            // Mage::dispatchEvent('smile_elasticsearch_index_delete_before', array('index_name' => $index));
            $this->client->indices()->delete(['index' => $deletedIndex]);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::createBulk()
     */
    public function createBulk()
    {
        // @todo
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface::executeBulk()
     */
    public function executeBulk($refreshIndex = true)
    {
        // @todo
    }

    /**
     *
     * @throws \LogicException
     *
     * @param unknown $indexIdentifier
     *
     * @return IndexInterface
     */
    private function initIndex($indexIdentifier, $store, $existingIndex)
    {
        $indicesConfiguration = $this->indicesConfiguration;
        $indexSettings        = $this->indexSettings;
        $indexAlias           = $indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
        $indexName            = false;

        if (isset($indicesConfiguration[$indexIdentifier])) {

            $indexName = $indexSettings->createIndexNameFromIdentifier($indexIdentifier, $store);

            $allowedFields = false;
            if ($existingIndex) {
                $indexName     = $indexAlias;
                //$allowedFields = $this->loadCurrentMapping($indexName);
            }

            $indexTypes = [];

            foreach ($indicesConfiguration[$indexIdentifier]['types'] as $typeName => $typeConfig) {
                $indexTypes[] = $this->initType($typeName, $typeConfig, $allowedFields);
            }

            $index = $this->objectManager->create(
                '\Smile\ElasticSuiteCore\Api\Index\IndexInterface',
                ['identifier' => $indexIdentifier, 'name' => $indexName, 'types' => $indexTypes]
            );

            $this->indices[$indexAlias] = $index;

        } else {
            throw new \LogicException("No index found with identifier {$indexIdentifier} into mapping.xml");
        }

        return $this->indices[$indexAlias];
    }

    private function initType($typeName, $typeConfig, $allowedFields)
    {
        $fieldDescriptions = $typeConfig['mapping']['fields'];

        $typeMapping = $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\Index\MappingInterface',
            ['fieldDescriptions' => $fieldDescriptions]
        );

        $currentType = $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\Index\TypeInterface',
            ['name' => $typeName, 'mapping' => $typeMapping]
        );

        return $type = $currentType;
    }
}