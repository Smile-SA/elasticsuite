<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
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
     * @return \Smile\ElasticSuiteCore\Api\Index\BulkInterface
     */
    public function createBulk();

    /**
     * @return ?????
     */
    public function executeBulk(BulkInterface $bulk, $refreshIndex = true);

    /**
     * @return int
     */
    public function getBatchIndexingSize();
}
