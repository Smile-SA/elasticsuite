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
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Helper;

use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Smile_ElasticSuiteCore search engine client configuration configuration default implementation.
 */
class IndexSettings extends AbstractConfiguration
{
    /**
     *
     * @var string
     */
    const INDICES_SETTINGS_CONFIG_XML_PREFIX = 'indices_settings';

    const LOCALE_CODE_CONFIG_XML_PATH = 'general/locale/code';

    /**
     * Read a configuration param under the SEARCH_CONFIG_XML_PREFIX ('catalog/search/elasticsearch_').
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

    private function getIndexAlias()
    {
        return $this->getIndicesSettingsConfigParam('alias');
    }

    /**
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     */
    private function getStoreCode($store)
    {
        return $this->getStore($store)->getCode();
    }

    /**
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     */
    private function getStore($store)
    {
        if (!is_object($store)) {
            $store = $this->storeManager->getStore($store);
        }
        return $store;
    }

    /**
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
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
     * @param \Magento\Store\Api\Data\StoreInterface $store
     */
    public function getLanguageCode($store)
    {
        $store = $this->getStore($store);
        $languageCode = current(explode('_', $this->getLocaleCode($store)));
        return $languageCode;

    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface::createIndexNameFromIdentifier()
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store)
    {
        // Generate the suffix of the index name from the current date
        // e.g : Default pattern "{{YYYYMMdd}}_{{HHmmss}}" is converted to "20160221-123421"
        $indiceNameSuffix = $this->getIndicesSettingsConfigParam('indices_pattern');

        $currentDate      = new \Zend_Date;

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
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface::getIndexAliasFromIdentifier()
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store)
    {
        $store = $this->getStoreCode($store);
        return sprintf('%s_%s_%s', $this->getIndexAlias(), $store, $indexIdentifier);
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface::getNumberOfShards()
     */
    public function getNumberOfShards()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_shards');
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface::getNumberOfReplicas()
     */
    public function getNumberOfReplicas()
    {
        return (int) $this->getIndicesSettingsConfigParam('number_of_replicas');
    }

    public function getBatchIndexingSize()
    {
        return (int) $this->getIndicesSettingsConfigParam('batch_indexing_size');
    }
}
