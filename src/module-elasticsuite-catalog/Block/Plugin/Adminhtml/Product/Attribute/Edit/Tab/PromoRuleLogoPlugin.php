<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block\Plugin\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Adds the ElasticSuite logo CSS class to the "Used for Promo Rules" product attribute field.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class PromoRuleLogoPlugin
{
    /**
     * Target field in product attribute form.
     */
    private const TARGET_FIELD_CODE = 'is_used_for_promo_rules';

    /**
     * ElasticSuite marker class.
     */
    private const ES_CLASS = 'es-esfeature__logo';

    /**
     * Modify element HTML after generation.
     *
     * @param AbstractElement $subject
     * @param string $html
     * @return string
     */
    public function afterGetHtml(AbstractElement $subject, string $html): string
    {
        // Ensure we touch ONLY our target field.
        if ($subject->getId() !== self::TARGET_FIELD_CODE) {
            return $html;
        }

        return $this->addClassToWrapperOnly($html);
    }

    /**
     * Inject ElasticSuite logo CSS class ONLY into admin__field wrapper.
     *
     * @param string $html
     * @return string
     */
    private function addClassToWrapperOnly(string $html): string
    {
        return preg_replace_callback(
            '/(<div[^>]*class="admin__field(?![_-])[^"]*)"/',
            function (array $match): string {
                $tag = $match[1];

                // Prevent duplicate injection.
                if (str_contains($tag, self::ES_CLASS)) {
                    return $match[0];
                }

                return $tag . ' ' . self::ES_CLASS . '"';
            },
            $html,
            1
        );
    }
}
