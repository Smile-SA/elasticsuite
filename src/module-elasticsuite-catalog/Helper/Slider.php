<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Slider class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class Slider extends AbstractHelper
{
    const XML_PATH_ADAPTIVE_SLIDER_ENABLED = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/adaptive_slider_enabled';

    /**
     * Is adaptive slider enabled ?
     *
     * @return bool
     */
    public function isAdaptiveSliderEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ADAPTIVE_SLIDER_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
