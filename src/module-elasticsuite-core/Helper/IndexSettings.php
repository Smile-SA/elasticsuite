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

namespace Smile\ElasticsuiteCore\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * Indices related configuration helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexSettings extends AbstractConfiguration
{
    /**
     * @var string
     */
    const INDICES_SETTINGS_CONFIG_XML_PREFIX = 'indices_settings';

    /**
     * @var string
     */
    const LOCALE_CODE_CONFIG_XML_PATH = 'general/locale/code';

    /**
     * @var integer
     */
    const PER_SHARD_MAX_RESULT_WINDOW = 100000;

    /**
     * Return the locale code (eg.: "en_US") for a store.
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store The store.
     *
     * @return string
     */
    public function getLocaleCode($store)
    {
        $configPath = self::LOCALE_CODE_CONFIG_XML_PATH;
        $scopeType  = ScopeInterface::SCOPE_STORES;
        $store      = $this->getStore($store);

        return $this->scopeConfig->getValue($configPath, $scopeType, $store);
    }

    /**
     * Return the locale code (eg.: "en") for a store.
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store The store.
     *
     * @return string
     */
    public function getLanguageCode($store)
    {
        $store = $this->getStore($store);
        $languageCode = current(explode('_', $this->getLocaleCode($store)));

        return $languageCode;
    }

    /**
     * Create a new index for an identifier (eg. catalog_product) by store including current date.
     *
     * @param string                                                $indexIdentifier Index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           The store.
     *
     * @return string
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store)
    {
        /*
         * Generate the suffix of the index name from the current date.
         * e.g : Default pattern "{{YYYYMMdd}}_{{HHmmss}}" is converted to "20160221-123421".
         */

        $indiceNameSuffix = $this->getIndicesSettingsConfigParam('indices_pattern');

        $currentDate      = new \Zend_Date();

        // Parse pattern to extract datetime tokens.
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $indiceNameSuffix, $matches);

        foreach (array_combine($matches[0], $matches[1]) as $k => $v) {
            // Replace tokens (UTC date used).
            $indiceNameSuffix = str_replace($k, $currentDate->toString($v), $indiceNameSuffix);
        }

        return sprintf('%s_%s', $this->getIndexAliasFromIdentifier($indexIdentifier, $store), $indiceNameSuffix);
    }

    /**
     * Returns the index alias for an identifier (eg. catalog_product) by store.
     *
     * @param string                                                $indexIdentifier An index identifier.
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store           The store.
     *
     * @return string
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store)
    {
        $store = strtolower($this->getStoreCode($store));

        return sprintf('%s_%s_%s', $this->getIndexAlias(), $store, $indexIdentifier);
    }

    /**
     * Get number of shards from the configuration.
     *
     * @return integer
     */
    public function getNumberOfShards()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_shards');
    }

    /**
     * Get number of replicas from the configuration.

     * @return integer
     */
    public function getNumberOfReplicas()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_replicas');
    }

    /**
     * Get number the batch indexing size from the configuration.
     *
     * @return integer
     */
    public function getBatchIndexingSize()
    {
        return (int) $this->getIndicesSettingsConfigParam('batch_indexing_size');
    }

    /**
     * Max number of results per query.
     *
     * @return integer
     */
    public function getMaxResultWindow()
    {
        return (int) $this->getNumberOfShards() * self::PER_SHARD_MAX_RESULT_WINDOW;
    }

    /**
     * Read config under the path smile_elasticsuite_core_base_settings/indices_settings.
     *
     * @param string $configField Configuration field name.
     *
     * @return mixed
     */
    private function getIndicesSettingsConfigParam($configField)
    {
        $path = self::INDICES_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->getElasticSuiteConfigParam($path);
    }

    /**
     * Get the index alias from the configurarion.
     *
     * @return string
     */
    private function getIndexAlias()
    {
        return $this->getIndicesSettingsConfigParam('alias');
    }

    /**
     * Retrieve the store code from object or store id.
     *
     * @param \Magento\Store\Api\Data\StoreInterface|integer $store The store or it's id.
     *
     * @return string
     */
    private function getStoreCode($store)
    {
        return $this->getStore($store)->getCode();
    }

    /**
     * Ensure store is an object or load it from it's id / identifier.
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store The store identifier or id.
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    private function getStore($store)
    {
        if (!is_object($store)) {
            $store = $this->storeManager->getStore($store);
        }

        return $store;
    }
}
