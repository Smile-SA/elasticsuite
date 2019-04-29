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
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus;

use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus edition form container
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
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
        parent::__construct($context, $data);
    }

    /**
     * Internal constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = ThesaurusInterface::THESAURUS_ID;
        $this->_blockGroup = 'Smile_ElasticsuiteThesaurus';
        $this->_controller = 'adminhtml_thesaurus';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Thesaurus'));

        $this->addButton(
            'save_and_edit_button',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ],
            ]
        );

        $this->buttonList->update('delete', 'label', __('Delete Thesaurus'));
    }
}
