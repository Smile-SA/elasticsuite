<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\BulkInterface;

/**
 * Default implementation of operation on indices (\Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;).
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexOperation implements IndexOperationInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Smile\ElasticSuiteCore\Api\Index\IndexInterface\IndexInterface[]
     */
    private $indicesByIdentifier = [];

    /**
     * @var \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface
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

    /**
     * Instanciate the index operation manager.
     *
     * @param \Magento\Framework\ObjectManagerInterface                 $objectManager
     * @param \Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface $clientFactory
     * @param \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface  $indexSettings
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ClientFactoryInterface $clientFactory,
        IndexSettingsInterface $indexSettings
    ) {
        $this->objectManager        = $objectManager;
        $this->client               = $clientFactory->createClient();
        $this->indexSettings        = $indexSettings;
        $this->indicesConfiguration = $indexSettings->getIndicesConfig();
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
            $exists = $this->client->indices()->exists(['index' => $indexName]);
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
                    "{$indexIdentifier} index does not exist yet. Make sure evything is reindexed"
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

        foreach ($index->getTypes() as $currentType) {
            $indexSettings['mappings'][$currentType->getName()] = $currentType->getMapping()->asArray();
        }

        $this->client->indices()->create(['index' => $index->getName(), 'body' => $indexSettings]);

        return $index;
    }

    /**
     * {@inheritDoc}
     */
    public function installIndex(IndexInterface $index, $store)
    {
        if ($index->needInstall()) {
            $indexIdentifier = $index->getIdentifier();
            $indexName       = $index->getName();
            $indexAlias      = $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);

            $this->client->indices()->optimize(['index' => $indexName]);
            $this->client->indices()->putSettings(
                ['index' => $indexName, 'body' => $this->indexSettings->getInstallIndexSettings()]
            );

            $this->proceedIndexInstall($indexName, $indexAlias);
        }

        return $index;
    }

    /**
     * {@inheritDoc}
     */
    public function createBulk()
    {
        return $this->objectManager->create('Smile\ElasticSuiteCore\Api\Index\BulkInterface');
    }

    /**
     * {@inheritDoc}
     */
    public function executeBulk(BulkInterface $bulk, $refreshIndex = true)
    {
        $bulkParams = ['body' => $bulk->getOperations()];
        $bulkResponse = $this->client->bulk($bulkParams);

        /** @todo Parse bulk response and report errors in logs*/

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
     *
     * @param string                                                $indexIdentifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     * @param boolean                                               $existingIndex
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface;
     */
    private function initIndex($indexIdentifier, $store, $existingIndex)
    {
        if (!isset($this->indicesConfiguration[$indexIdentifier])) {
            throw new \LogicException("No index found with identifier {$indexIdentifier} into mapping.xml");
        }

        $indexSettings    = $this->indexSettings;
        $indexAlias       = $indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $store);
        $indexName        = $indexSettings->createIndexNameFromIdentifier($indexIdentifier, $store);
        $indexNeedInstall = !$existingIndex;

        if ($existingIndex) {
            $indexName     = $indexAlias;
            $createIndexParams['needInstall'] = true;
        }

        $createIndexParams = ['identifier' => $indexIdentifier, 'name' => $indexName, 'needInstall' => true];

        $createIndexParams['types'] = $this->indicesConfiguration[$indexIdentifier]['types'];

        $index = $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\Index\IndexInterface',
            $createIndexParams
        );

        $this->indicesByIdentifier[$indexAlias] = $index;

        return $this->indicesByIdentifier[$indexAlias];
    }

    /**
     * Proceed to the indices install :
     *
     *  1) First switch the alias to the new index
     *  2) Remove old indices
     *
     * @param string $indexName  Real index name.
     * @param string $indexAlias Index alias (must include store identifier).
     *
     * @return void
     */
    private function proceedIndexInstall($indexName, $indexAlias)
    {
        $aliasActions   = [['add' => ['index' => $indexName, 'alias' => $indexAlias]]];
        $deletedIndices = [];

        try {
            $oldIndices = $this->client->indices()->getMapping(['index' => $indexAlias]);
        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            $oldIndices = [];
        }

        foreach (array_keys($oldIndices) as $oldIndexName) {
            if ($oldIndexName != $indexName) {
                $deletedIndices[] = $oldIndexName;
                $aliasActions[]   = ['remove' => ['index' => $oldIndexName, 'alias' => $indexAlias]];
            }
        }

        $this->client->indices()->updateAliases(['body' => ['actions' => $aliasActions]]);

        foreach ($deletedIndices as $deletedIndex) {
            // @todo : Dispatch event
            // Mage::dispatchEvent('smile_elasticsearch_index_delete_before', array('index_name' => $index));
            $this->client->indices()->delete(['index' => $deletedIndex]);
        }
    }
}
