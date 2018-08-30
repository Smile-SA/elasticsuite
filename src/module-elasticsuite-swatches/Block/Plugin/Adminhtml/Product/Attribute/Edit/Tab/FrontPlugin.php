<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteSwatches\Block\Plugin\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front;
use Magento\Framework\Data\Form;

/**
 * Plugin that disable the facet_max_size field for swatches attributes.
 *
 * @category Smile
 * @package  Smile\ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FrontPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Swatches\Model\SwatchAttributeType
     */
    private $swatchAttributeType;

    /**
     * FrontPlugin constructor.
     *
     * @param \Magento\Framework\Registry                 $registry            Registry
     * @param \Magento\Swatches\Model\SwatchAttributeType $swatchAttributeType Swatch Attribute Type
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Swatches\Model\SwatchAttributeType $swatchAttributeType
    ) {
        $this->registry            = $registry;
        $this->swatchAttributeType = $swatchAttributeType;
    }

    /**
     * Disable facet_max_size field for swatches attributes.
     *
     * @param \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front $subject The StoreFront tab
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front
     */
    public function afterSetForm(\Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front $subject)
    {
        if ($this->getAttribute() && $this->swatchAttributeType->isSwatchAttribute($this->getAttribute())) {
            if ($subject->getForm() && $subject->getForm()->getElement('facet_max_size')) {
                $subject->getForm()->getElement('facet_max_size')->setDisabled(true);
            }
        }

        return $subject;
    }

    /**
     * Return the current edit attribute.
     *
     * @return \Magento\Catalog\Api\Data\EavAttributeInterface
     */
    private function getAttribute()
    {
        return $this->registry->registry('entity_attribute');
    }
}
