<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Block\Adminhtml\Thesaurus;

/**
 * Thesaurus creation form container
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Create extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context  Current context
     * @param \Magento\Framework\Registry           $registry The registry
     * @param array                                 $data     Block parameters
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->_mode = "create";

        parent::__construct($context, $data);

        $this->setFormActionUrl($this->getUrl('*/*/create'));
    }

    /**
     * Internal constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_objectId = 'id';
        $this->_blockGroup = 'Smile_ElasticSuiteThesaurus';
        $this->_controller = 'adminhtml_thesaurus';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Create Thesaurus'));
    }
}
