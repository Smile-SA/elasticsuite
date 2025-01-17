<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Helper;

/**
 * Autocomplete Settings related helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Autocomplete extends AbstractConfiguration
{
    /**
     * @var string
     */
    const AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX = 'smile_elasticsuite_autocomplete_settings';

    /**
     * Returns max size for a given type of autocomplete results
     *
     * @param string $type The type of autocomplete element.
     *
     * @return int
     */
    public function getMaxSize($type)
    {
        return (int) $this->getConfigValue(sprintf("%s_autocomplete/max_size", $type));
    }

    /**
     * Returns if an autocomplete type is enabled or not.
     *
     * @param string $type The type of autocomplete element.
     *
     * @return boolean
     */
    public function isEnabled($type)
    {
        return $this->getMaxSize($type) > 0;
    }

    /**
     * Check if Autocomplete "extension" system is enabled.
     *
     * @return bool
     */
    public function isExtensionEnabled()
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/advanced/extension_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Autocomplete "extension" system is limited.
     *
     * @return bool
     */
    public function isExtensionLimited()
    {
        return (bool) ($this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/advanced/extension_limited",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) && ($this->getExtensionSize() > 0);
    }

    /**
     * Get the maximum number of popular search terms to use when the "extension" is limited.
     *
     * @return int
     */
    public function getExtensionSize()
    {
        return (int) $this->getConfigValue("advanced/extension_size");
    }

    /**
     * Check if Autocomplete "extension" system is stopped when having matches.
     *
     * @return bool
     */
    public function isExtensionStoppedOnMatch()
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/advanced/stop_extension_on_match",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Autocomplete is supposed to always use the user raw query or not.
     *
     * @return bool
     */
    public function isPreservingBaseQuery()
    {
        return (bool) $this->scopeConfig->isSetFlag(
            self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/advanced/preserve_base_query",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     *
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(
            self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/" . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
