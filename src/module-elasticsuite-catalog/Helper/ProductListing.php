<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Helper;

use Smile\ElasticsuiteCore\Helper\AbstractConfiguration;

/**
 * Class product_listing
 */
class ProductListing extends AbstractConfiguration
{
    /**
     * @var string
     */
    const PRODUCT_LISTING_SETTINGS_CONFIG_XML_PREFIX = 'smile_elasticsuite_product_listing_settings';

    /**
     * Returns if an autocomplete type is enabled or not.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool) $this->getConfigValue('product_listing/use_for_product_listing');
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
        return $this->scopeConfig->getValue(self::PRODUCT_LISTING_SETTINGS_CONFIG_XML_PREFIX . "/" . $key);
    }
}
