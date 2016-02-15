<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;

use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;

class IndexerHandler implements IndexerInterface
{

    const INDEX_NAME = 'catalog_product';
    const TYPE_NAME  = 'product';

    /**
     * @var \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     * @var \Magento\Framework\Indexer\SaveHandler\Batch
     */
    private $batch;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(IndexOperationInterface $indexOperation, Batch $batch)
    {
        $this->indexOperation = $indexOperation;
        $this->batchSize      = $indexOperation->getBatchIndexingSize();
        $this->batch          = $batch;
    }

    public function saveIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {

            $storeId = $dimension->getValue();
            $index = $this->indexOperation->getIndexByName(self::INDEX_NAME, $storeId);
            $type  = $index->getType(self::TYPE_NAME);

            foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {

                foreach ($type->getDatasources() as $datasource) {
                    $batchDocuments = $datasource->addData($storeId, $batchDocuments);
                }

                $bulk = $this->indexOperation->createBulk()->addDocuments($index, $type, $batchDocuments);
                $this->indexOperation->executeBulk($bulk);
            }
            $this->indexOperation->installIndex($index, $storeId);
        }
        return $this;
    }

    public function deleteIndex($dimensions, \Traversable $documents)
    {
        foreach ($dimensions as $dimension) {
            //$this->indexOperation->createIndex(self::INDEX_NAME, $dimension->getValue());
        }

        return $this;
    }

    public function cleanIndex($dimensions)
    {
        foreach ($dimensions as $dimension) {
            $this->indexOperation->createIndex(self::INDEX_NAME, $dimension->getValue());
        }
        return $this;
    }

    public function isAvailable()
    {
        return true;
    }
}
