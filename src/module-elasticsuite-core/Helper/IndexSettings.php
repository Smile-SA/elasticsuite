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
use Zend_Date;

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
     * @var integer
     */
    const MIN_SHINGLE_SIZE_DEFAULT = 2;

    /**
     * @var integer
     */
    const MAX_SHINGLE_SIZE_DEFAULT = 2;

    /**
     * @var integer
     */
    const MIN_NGRAM_SIZE_DEFAULT = 1;

    /**
     * @var integer
     */
    const MAX_NGRAM_SIZE_DEFAULT = 2;

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
    public function createIndexNameFromIdentifier($indexIdentifier, $store): string
    {
        $indexNameSuffix = $this->getIndexNameSuffix(new Zend_Date());

        return sprintf('%s_%s', $this->getIndexAliasFromIdentifier($indexIdentifier, $store), $indexNameSuffix);
    }

    /**
     * Get index name suffix.
     *
     * @param Zend_Date $date Date
     * @return string
     */
    public function getIndexNameSuffix(Zend_Date $date): string
    {
        /*
        * Generate the suffix of the index name from the current date.
        * e.g : Default pattern "{{YYYYMMdd}}_{{HHmmss}}" is converted to "20160221_123421".
        */
        $indexNameSuffix = $this->getIndicesSettingsConfigParam('indices_pattern');

        // Parse pattern to extract datetime tokens.
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $indexNameSuffix, $matches);

        foreach (array_combine($matches[0], $matches[1]) as $k => $v) {
            // Replace tokens (UTC date used).
            $indexNameSuffix = str_replace($k, $date->toString($v), $indexNameSuffix);
        }

        return $indexNameSuffix;
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
     * Get the indices pattern from the configuration.
     *
     * @return string
     */
    public function getIndicesPattern(): string
    {
        return $this->getIndicesSettingsConfigParam('indices_pattern');
    }

    /**
     * Get the index alias from the configuration.
     *
     * @return string
     */
    public function getIndexAlias(): string
    {
        return $this->getIndicesSettingsConfigParam('alias');
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
     * Get maximum shingle diff for an index.
     *
     * @param array $analysisSettings Analysis Settings
     *
     * @return int|false
     */
    public function getMaxShingleDiff($analysisSettings)
    {
        $maxShingleDiff = false;
        foreach ($analysisSettings['filter'] ?? [] as $filter) {
            if (($filter['type'] ?? null) === 'shingle') {
                // @codingStandardsIgnoreStart
                $filterDiff = ($filter['max_shingle_size'] ?? self::MAX_SHINGLE_SIZE_DEFAULT)
                    - ($filter['min_shingle_size'] ?? self::MIN_SHINGLE_SIZE_DEFAULT);
                // codingStandardsIgnoreEnd
                $maxShingleDiff = max((int) $maxShingleDiff, $filterDiff) + 1;
            }
        }

        return $maxShingleDiff;
    }

    /**
     * Get maximum ngram diff for an index.
     *
     * @param array $analysisSettings Analysis Settings
     *
     * @return int|false
     */
    public function getMaxNgramDiff($analysisSettings)
    {
        $maxNgramDiff = false;
        foreach ($analysisSettings['filter'] ?? [] as $filter) {
            if (in_array(($filter['type'] ?? null), ['ngram', 'edge_ngram'])) {
                $filterDiff = ($filter['max_gram'] ?? self::MAX_NGRAM_SIZE_DEFAULT)
                    - ($filter['min_gram'] ?? self::MIN_NGRAM_SIZE_DEFAULT);

                $maxNgramDiff = max((int) $maxNgramDiff, $filterDiff) + 1;
            }
        }

        return $maxNgramDiff;
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
