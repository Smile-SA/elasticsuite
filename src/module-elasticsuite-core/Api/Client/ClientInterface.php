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

namespace Smile\ElasticsuiteCore\Api\Client;

/**
 * ElasticSearch injectable client.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface ClientInterface
{
    /**
     * Returns server information.
     *
     * @return array
     */
    public function info();

    /**
     * Try to connect the server and returns :
     * - true if succeed
     * - false if failed
     *
     * @return boolean
     */
    public function ping();

    /**
     * Create an index.
     *
     * @param string $indexName     Index name.
     * @param array  $indexSettings Index settings.
     *
     * @return void
     */
    public function createIndex($indexName, $indexSettings);

    /**
     * Delete an index.
     *
     * @param string $indexName Index name.
     *
     * @return void
     */
    public function deleteIndex($indexName);

    /**
     * Check if an index exists.
     *
     * @param string $indexName Index name.
     *
     * @return boolean
     */
    public function indexExists($indexName);

    /**
     * Update index settings.
     *
     * @param string $indexName     Index name.
     * @param array  $indexSettings Index settings.
     *
     * @return void
     */
    public function putIndexSettings($indexName, $indexSettings);

    /**
     * Update index mapping.
     *
     * @param string $indexName Index name.
     * @param string $type      Type.
     * @param array  $mapping   Mapping definition.
     *
     * @return void
     */
    public function putMapping($indexName, $type, $mapping);

    /**
     * Optimize an index (force segment merging).
     *
     * @param string $indexName Index name.
     *
     * @return void
     */
    public function forceMerge($indexName);

    /**
     * Force index refresh.
     *
     * @param string $indexName Index name.
     *
     * @return void
     */
    public function refreshIndex($indexName);

    /**
     * Retrieve the list of all index having a specified alias.
     *
     * @param string $indexAlias Index alias.
     *
     * @return string[]
     */
    public function getIndicesNameByAlias($indexAlias);

    /**
     * Update alias definition.
     *
     * @param array $aliasActions Alias actions.
     *
     * @return void
     */
    public function updateAliases($aliasActions);

    /**
     * Run a bulk request.
     *
     * @param array $bulkParams Bulk data.
     *
     * @return array
     */
    public function bulk($bulkParams);

    /**
     * Run a search request.
     *
     * @param array $params Search request params.
     *
     * @return array
     */
    public function search($params);

    /**
     * Run an analyze request using ElasticSearch.
     *
     * @param array $params Analyze params.
     *
     * @return array
     */
    public function analyze($params);

    /**
     * Returns index stats.
     *
     * @param string $indexName Index name.
     *
     * @return array
     */
    public function indexStats($indexName);

    /**
     * Run a termvectors request.
     *
     * @param array $params Term vectors request params.
     *
     * @return array
     */
    public function termvectors($params);
}
