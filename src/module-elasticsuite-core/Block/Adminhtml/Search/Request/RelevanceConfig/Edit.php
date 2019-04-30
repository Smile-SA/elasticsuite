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

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Relevance Configuration edit form
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName) The property _template is inherited
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Edit extends \Magento\Backend\Block\Widget
{
    const DEFAULT_SECTION_BLOCK = 'Smile\ElasticsuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig\Form';

    /**
     * Form block class name
     *
     * @var string
     */
    protected $formBlockName;

    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Magento_Config::system/config/edit.phtml';

    /**
     * Configuration structure
     *
     * @var Structure
     */
    protected $configStructure;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * Class constructor
     *
     * @param Context   $context         Application context
     * @param Structure $configStructure Configuration Structure
     * @param Json      $jsonSerializer  JSON Serializer
     * @param array     $data            The data
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->configStructure = $configStructure;
        $this->jsonSerializer  = $jsonSerializer;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve rendered save buttons
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    /**
     * Retrieve config save url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getConfigSearchParamsJson()
    {
        $params = [];

        if ($this->getRequest()->getParam('section')) {
            $params['section'] = $this->getRequest()->getParam('section');
        }

        if ($this->getRequest()->getParam('group')) {
            $params['group'] = $this->getRequest()->getParam('group');
        }

        if ($this->getRequest()->getParam('field')) {
            $params['field'] = $this->getRequest()->getParam('field');
        }

        return $this->jsonSerializer->serialize($params);
    }

    /**
     * Prepare layout object
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        /** @var $section \Magento\Config\Model\Config\Structure\Element\Section */
        $section = $this->configStructure->getElement($this->getRequest()->getParam('section'));
        $this->formBlockName = $section->getFrontendModel();
        if (empty($this->formBlockName)) {
            $this->formBlockName = self::DEFAULT_SECTION_BLOCK;
        }
        $this->setTitle($section->getLabel());
        $this->setHeaderCss($section->getHeaderCss());

        $this->getToolbar()->addChild(
            'save_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'id'             => 'save',
                'label'          => __('Save Config'),
                'class'          => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save', 'target' => '#config-edit-form']],
                ],
            ]
        );
        $block = $this->getLayout()->createBlock($this->formBlockName);
        $this->setChild('form', $block);

        return parent::_prepareLayout();
    }
}
