<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Magento\Framework\Indexer\SaveHandler\Batch;

/**
 * Eav Indexing operation handling for Elasticsearch engine.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class GenericIndexerHandler implements IndexerInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     * @var \Magento\Framework\Indexer\SaveHandler\Batch
     */
    private $batch;

    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $typeName;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterfaceFactory
     */
    private $dataSourceResolverFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * Constructor
     *
     * @param IndexOperationInterface            $indexOperation            Index operation service.
     * @param CacheHelper                        $cacheHelper               Index caching helper.
     * @param Batch                              $batch                     Batch handler.
     * @param DataSourceResolverInterfaceFactory $dataSourceResolverFactory DataSource resolver.
     * @param IndexSettingsInterface             $indexSettings             Index Settings.
     * @param string                             $indexName                 The index name.
     * @param string                             $typeName                  The type name.
     */
    public function __construct(
        IndexOperationInterface $indexOperation,
        CacheHelper $cacheHelper,
        Batch $batch,
        DataSourceResolverInterfaceFactory $dataSourceResolverFactory,
        IndexSettingsInterface $indexSettings,
        $indexName,
        $typeName
    ) {
        $this->indexOperation            = $indexOperation;
        $this->batch                     = $batch;
        $this->indexName                 = $indexName;
        $this->typeName                  = $typeName;
        $this->cacheHelper               = $cacheHelper;
        $this->dataSourceResolverFactory = $dataSourceResolverFactory;
        $this->indexSettings             = $indexSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {
            $storeId   = $dimension->getValue();

            try {
                $index = $this->indexOperation->getIndexByName($this->indexName, $storeId);
            } catch (\Exception $e) {
                $index = $this->indexOperation->createIndex($this->indexName, $storeId);
            }

            $type      = $index->getType($this->typeName);
            $batchSize = $this->indexOperation->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $batchDocuments) {
                foreach ($this->getDatasources($this->indexName) as $datasource) {
                    if (!empty($batchDocuments)) {
                        $batchDocuments = $datasource->addData($storeId, $batchDocuments);
                    }
                }

                if (!empty($batchDocuments)) {
                    $bulk = $this->indexOperation->createBulk()->addDocuments($index, $type, $batchDocuments);
                    $this->indexOperation->executeBulk($bulk);
                }
            }

            $this->indexOperation->refreshIndex($index);
            $this->indexOperation->installIndex($index, $storeId);
            $this->cacheHelper->cleanIndexCache($this->indexName, $storeId);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {
            $storeId   = $dimension->getValue();

            if ($this->indexOperation->indexExists($this->indexName, $storeId)) {
                $index     = $this->indexOperation->getIndexByName($this->indexName, $storeId);
                $type      = $index->getType($this->typeName);
                $batchSize = $this->indexOperation->getBatchIndexingSize();

                foreach ($this->batch->getItems($documents, $batchSize) as $batchDocuments) {
                    $bulk = $this->indexOperation->createBulk()->deleteDocuments($index, $type, $batchDocuments);
                    $this->indexOperation->executeBulk($bulk);
                }

                $this->indexOperation->refreshIndex($index);
            }
        }

        return $this;
    }

    /**
     * This override does not delete data into the old index as expected but only create a new index.
     * It allows to keep old index in place during full reindex.
     *
     * {@inheritDoc}
     */
    public function cleanIndex($dimensions)
    {
        foreach ($dimensions as $dimension) {
            $this->indexOperation->createIndex($this->indexName, $dimension->getValue());
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable($dimensions = [])
    {
        return $this->indexOperation->isAvailable();
    }

    /**
     * Retrieve data sources of an index by name.
     *
     * @deprecated
     *
     * @param string $indexName The index name
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface[]
     */
    private function getDatasources($indexName)
    {
        $config            = $this->indexSettings->getIndexConfig($indexName);
        $legacyDatasources = $config['datasources'] ?? [];

        $resolver = $this->dataSourceResolverFactory->create(['legacyDataSources' => [$indexName => $legacyDatasources]]);
        $sources  = $resolver->getDataSources($this->indexName);

        return $sources;
    }
}
