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

namespace Smile\ElasticSuiteCore\Helper;

use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Indices related configuration helper.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
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
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
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
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
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
     *
     *
     * @param string                                                $indexIdentifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store)
    {
        // Generate the suffix of the index name from the current date
        // e.g : Default pattern "{{YYYYMMdd}}_{{HHmmss}}" is converted to "20160221-123421"
        $indiceNameSuffix = $this->getIndicesSettingsConfigParam('indices_pattern');

        $currentDate      = new \Zend_Date();

        // Parse pattern to extract datetime tokens
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $indiceNameSuffix, $matches);

        foreach (array_combine($matches[0], $matches[1]) as $k => $v) {
            // Replace tokens (UTC date used)
            $indiceNameSuffix = str_replace($k, $currentDate->toString($v), $indiceNameSuffix);
        }

        return sprintf('%s_%s', $this->getIndexAliasFromIdentifier($indexIdentifier, $store), $indiceNameSuffix);
    }

    /**
     *
     *
     * @param string                                                $indexIdentifier
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store)
    {
        $store = $this->getStoreCode($store);

        return sprintf('%s_%s_%s', $this->getIndexAlias(), $store, $indexIdentifier);
    }

    /**
     *
     * @return integer
     */
    public function getNumberOfShards()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_shards');
    }

    /**
     *
     * @return integer
     */
    public function getNumberOfReplicas()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_replicas');
    }

    /**
     *
     * @return integer
     */
    public function getBatchIndexingSize()
    {
        return (int) $this->getIndicesSettingsConfigParam('batch_indexing_size');
    }

    /**
     * Read config under the path smile_elasticsuite_core_base_settings/indices_settings.
     *
     * @param string $configField
     *
     * @return mixed
     */
    private function getIndicesSettingsConfigParam($configField)
    {
        $path = self::INDICES_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->getElasticSuiteConfigParam($path);
    }

    /**
     *
     * @return string
     */
    private function getIndexAlias()
    {
        return $this->getIndicesSettingsConfigParam('alias');
    }

    /**
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     */
    private function getStoreCode($store)
    {
        return $this->getStore($store)->getCode();
    }

    /**
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store
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
