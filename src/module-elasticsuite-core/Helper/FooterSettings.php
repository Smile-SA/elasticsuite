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
        return (bool) $this->getFooterSettingsConfigParam('enable_es_link');
    }

    /**
     * Read config under the path smile_elasticsuite_core_base_settings/footer_settings.
     *
     * @param string $configField Configuration field name.
     *
     * @return mixed
     */
    private function getFooterSettingsConfigParam(string $configField)
    {
        $path = self::FOOTER_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->getElasticSuiteConfigParam($path);
    }
}
