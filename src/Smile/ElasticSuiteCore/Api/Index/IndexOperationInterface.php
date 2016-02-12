<?php

namespace Smile\ElasticSuiteCore\Api\Index;

interface IndexOperationInterface
{
    /**
     *
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * @param string $indexName
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function getIndexByName($indexIdentifier, $store);

    /**
     * @param string $indexName
     *
     * @return bool
     */
    public function indexExists($indexIdentifier, $store);

    /**
     * @param Index $indexName
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function createIndex($indexIdentifier, $store);

    /**
     * @param Index $index
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function installIndex(IndexInterface $index, $store);

    /**
     * @return ?????
     */
    public function createBulk();


    /**
     * @return ?????
     */
    public function executeBulk($refreshIndex = true);

}