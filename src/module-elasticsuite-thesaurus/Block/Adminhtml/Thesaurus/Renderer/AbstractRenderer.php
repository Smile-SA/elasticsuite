<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer;

/**
 * Abstract Renderer for array-composite fields
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractRenderer extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var integer Size of the textarea to display
     */
    protected $textAreaColsNumber = 100;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context      $context        Application context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory Element Factory
     * @param array                                        $data           Element Data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;

        parent::__construct($context, $data);
    }

    /**
     * Render array cell for JS template
     *
     * @param string $columnName The column name
     *
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'term_id' && isset($this->_columns[$columnName])) {
            $element = $this->elementFactory->create('hidden');
            $element->setId("term_id")->setName("term_id");
            $element->setForm($this->getForm())
                ->setName($this->_getCellInputElementName($columnName))
                ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName));

            return $element->getElementHtml();
        }

        if ($columnName == 'values' && isset($this->_columns[$columnName])) {
            $element = $this->elementFactory->create('textarea');
            $element->setCols($this->textAreaColsNumber)
                ->setForm($this->getForm())
                ->setName($this->_getCellInputElementName($columnName))
                ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName));

            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Render given element (return html of element)
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element The element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->getValues()) {
            $element->setValue($this->getValues());
        }

        $this->setElement($element);

        return $this->toHtml();
    }
}
