<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
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
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     *
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/" . $key);
    }
}
