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

/**
 * Provides access to indices related settings / configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface IndexSettingsInterface
{
    /**
     * Returns the index alias for an identifier (eg. catalog_product) by store.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store.
     *
     * @return string
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store);

    /**
     * Create a new index for an identifier (eg. catalog_product) by store including current date.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           Store.
     *
     * @return string
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store);

    /**
     * Load analysis settings by store.
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store Store.
     *
     * @return array
     */
    public function getAnalysisSettings($store);

    /**
     * Returns settings used during index creation.
     *
     * @param string $indexIdentifier Index identifier.
     *
     * @return array
     */
    public function getCreateIndexSettings($indexIdentifier);

    /**
     * Returns settings used when installing an index.
     *
     * @param string $indexIdentifier Index identifier.
     *
     * @return array
     */
    public function getInstallIndexSettings($indexIdentifier);

    /**
     * Returns the list of the available indices declared in elasticsuite_indices.xml.
     *
     * @return array
     */
    public function getIndicesConfig();

    /**
     * Return config of an index.
     *
     * @param string $indexIdentifier Index identifier.
     *
     * @return array
     */
    public function getIndexConfig($indexIdentifier);

    /**
     * Get indexing batch size configured.
     *
     * @return integer
     */
    public function getBatchIndexingSize();

    /**
     * Get dynamic index settings per store (language).
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store Store.
     *
     * @return array
     */
    public function getDynamicIndexSettings($store);
}
