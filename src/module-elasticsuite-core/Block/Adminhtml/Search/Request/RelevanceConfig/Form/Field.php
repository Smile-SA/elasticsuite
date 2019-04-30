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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig\Form;

/**
 * Relevance configuration form field renderer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Check if inheritance checkbox has to be rendered
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element The form element
     *
     * @return bool
     */
    protected function _isInheritCheckboxRequired(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $element->getCanUseContainerValue() || $element->getCanUseDefaultValue();
    }

    /**
     * Retrieve label for the inheritance checkbox
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element The form element
     *
     * @return string
     */
    protected function _getInheritCheckboxLabel(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $checkboxLabel = __('Use Default');
        if ($element->getCanUseContainerValue()) {
            $checkboxLabel = __('Use Container');
        }

        return $checkboxLabel;
    }
}
