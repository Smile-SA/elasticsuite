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
 * Footer Settings related helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class FooterSettings extends AbstractConfiguration
{
    /**
     * Location of ElasticSuite misc settings configuration.
     *
     * @var string
     */
    const MISC_SETTINGS_XML_PREFIX = 'smile_elasticsuite_misc_settings';

    /**
     * Prefix of ElasticSuite footer configuration.
     *
     * @var string
     */
    const FOOTER_SETTINGS_CONFIG_XML_PREFIX = 'footer_settings';

    /**
     * Returns if the ElasticSuite copyright link is enabled or not to display in the footer.
     *
     * @return bool
     */
    public function isEsLinkEnabled(): bool
    {
        return (bool) $this->getMiscSettingsConfigParam(self::FOOTER_SETTINGS_CONFIG_XML_PREFIX . '/' . 'enable_es_link');
    }

    /**
     * Read config under the path smile_elasticsuite_misc_settings/*.
     *
     * @param string $configField Configuration field name.
     *
     * @return mixed
     */
    private function getMiscSettingsConfigParam(string $configField)
    {
        $path = self::MISC_SETTINGS_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}
