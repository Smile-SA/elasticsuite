<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalogAutocomplete
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalogAutocomplete\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Autocomplete Settings related helper
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogAutocomplete
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Configuration extends AbstractHelper
{
    /**
     * @var string
     */
    const AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX = 'smile_elasticsuite_catalogautocomplete';

    /**
     * @var string
     */
    const PRODUCT_AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX = 'product_autocomplete';

    /**
     * Retrieve Max Size for products results
     *
     * @return int
     */
    public function getProductsMaxSize()
    {
        return (int) $this->getConfigValue(self::PRODUCT_AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/max_size");
    }

    /**
     * Retrieve a configuration value by its key
     *
     * @param string $key The configuration key
     *
     * @return mixed
     */
    private function getConfigValue($key)
    {
        return $this->scopeConfig->getValue(self::AUTOCOMPLETE_SETTINGS_CONFIG_XML_PREFIX . "/" . $key);
    }
}
