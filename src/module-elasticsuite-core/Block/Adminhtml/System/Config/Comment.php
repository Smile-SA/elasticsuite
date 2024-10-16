<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * System config comment block.
 * Allows to display a label and a comment without a field label and the scope input.
 * This allows to display a comment at default scope for fields only available at the store view level.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class Comment extends Field
{
    /**
     * Retrieve HTML markup for given form element.
     *
     * @param AbstractElement $element Form element.
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '<div class="elasticsuite config-comment-title">' . $element->getLabel() . '</div>';
        $html .= '<div class="elasticsuite config-comment-content">' . $element->getComment() . '</div>';

        return $this->decorateRowHtml($element, $html);
    }

    /**
     * Decorate field row html.
     *
     * @param AbstractElement $element Form element.
     * @param string          $html    Field row html.
     *
     * @return string
     */
    private function decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html)
    {
        return sprintf(
            '<tr id="row_%s"><td colspan="3"><div class="elasticsuite config-comment">%s</div></td></tr>',
            $element->getHtmlId(),
            $html
        );
    }
}
