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
namespace Smile\ElasticSuiteCore\Block\Adminhtml\Relevance\Config\Form;

/**
 * Relevance configuration form field renderer
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Check if inheritance checkbox has to be rendered
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element The form element
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // @codingStandardsIgnoreEnd
        return $element->getCanUseContainerValue() || $element->getCanUseDefaultValue();
    }

    /**
     * Retrieve label for the inheritance checkbox
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element The form element
     *
     * @return string
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _getInheritCheckboxLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // @codingStandardsIgnoreEnd
        $checkboxLabel = __('Use Default');
        if ($element->getCanUseContainerValue()) {
            $checkboxLabel = __('Use Container');
        }

        return $checkboxLabel;
    }
}
