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

namespace Smile\ElasticsuiteCore\Api\Index;

use Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface;

/**
 * Operation on indices (create, get, install, indexing).
 *
 * @category Smile
 * @package  SSmile_ElasticsuiteCore
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
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
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
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    public function createIndex($indexIdentifier, $store);

    /**
     * Updated the mapping of an index according to current computed mapping
     * This is use as a real-time update when changing field configurations.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store (id, identifier or object).
     * @param array                                                 $fields          The fields to update. Default to all.
     *
     * @return void
     */
    public function updateMapping($indexIdentifier, $store, $fields = []);

    /**
     * Switch the alias to the installed index and delete the old index.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface      $index Installed index.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store Store (id, identifier or object).
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexInterface
     */
    public function installIndex(IndexInterface $index, $store);

    /**
     * Create a new empty bulk.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface
     */
    public function createBulk();

    /**
     * Execute a bulk and return the execution response.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkRequestInterface $bulk Bulk to be executed.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Bulk\BulkResponseInterface
     */
    public function executeBulk(BulkRequestInterface $bulk);

    /**
     * Refresh an index (should be called after indexing operations to ensure data are available for search).
     *
     * @param IndexInterface $index Index
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    public function refreshIndex(IndexInterface $index);

    /**
     * Returns batch indexing size.
     *
     * @return integer
     */
    public function getBatchIndexingSize();

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
    public function proceedIndexInstall($indexName, $indexAlias);

    /**
     * Copies documents from a source to a destination, the source can be any existing index, alias, or data stream.
     *
     * @param array  $sourceIndices Source indices
     * @param string $destIndex     Dest index.
     * @param array  $bodyParams    Body params.
     * @param array  $sourceParams  Extra source params.
     * @param array  $destParams    Extra dest params.
     *
     * @return array
     */
    public function reindex(
        array $sourceIndices,
        string $destIndex,
        array $bodyParams = [],
        array $sourceParams = [],
        array $destParams = []
    ): array;
}
