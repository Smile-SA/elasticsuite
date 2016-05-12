<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Helper;

/**
 * Autocomplete Settings related helper
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Autocomplete extends AbstractConfiguration
{
    /**
     * @var string
     */
    const AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX = 'smile_elasticsuite_autocomplete_settings';

    /**
     * Retrieve Max Size for a given type of autocomplete results
     *
     * @param string $type The type of autocomplete element
     *
     * @return int
     */
    public function getMaxSize($type = null)
    {
        if ($type === null) {
            throw new \LogicException('Autocomplete type is missing');
        }

        $configPath = sprintf("%s_autocomplete/max_size", $type);

        return (int) $this->getConfigValue($configPath);
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
