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

namespace Smile\ElasticSuiteCore\Api\Index;

/**
 * Operation on indices (create, get, install, indexing).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface IndexOperationInterface
{
    /**
     * Check if the ES service is available.
     *
     * @return boolean
     */
    public function isAvailable();

    /**
     * Returns an index by it's identifier (e.g.: catalog_product) and by store.
     * If the index does not exists into ES a \LogicalException is thrown.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store (id, identifier or object).
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function getIndexByName($indexIdentifier, $store);

    /**
     * Check if an index exists using it's identifier (e.g.: catalog_product) and by store.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store (id, identifier or object).
     *
     * @return boolean
     */
    public function indexExists($indexIdentifier, $store);

    /**
     * Prepare a new index into ES from it's identifier (e.g.: catalog_product).
     * The index is created with a horodated name prefixed by the alias (eg.: magento2_catalog_product_2060201_122145).
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store (id, identifier or object).
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function createIndex($indexIdentifier, $store);

    /**
     * Switch the alias to the installed index and delete the old index.
     *
     * @param \Smile\ElasticSuiteCore\Api\Index\IndexInterface      $index Installed index.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store Store (id, identifier or object).
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexInterface
     */
    public function installIndex(IndexInterface $index, $store);

    /**
     * Create a new empty bulk.
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\BulkInterface
     */
    public function createBulk();

    /**
     * Execute a bulk.
     *
     * @param \Smile\ElasticSuiteCore\Api\Index\BulkInterface $bulk         Bulk to be executed.
     * @param boolean                                         $refreshIndex Force index to be refreshed
     *                                                                      after bulk execution.
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface
     */
    public function executeBulk(BulkInterface $bulk, $refreshIndex = true);

    /**
     * Returns batch indexing size.
     *
     * @return integer
     */
    public function getBatchIndexingSize();
}
