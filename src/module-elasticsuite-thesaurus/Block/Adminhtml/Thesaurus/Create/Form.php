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
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Create;

use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus creation form
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Init Form properties
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('thesaurus_create_form');
        $this->setTitle(__('Create a Thesaurus'));
    }

    /**
     * Prepare form fields
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action')]]
        );

        $thesaurusTypes = [
            ['value' => ThesaurusInterface::TYPE_SYNONYM, 'label' => __('Synonym')],
            ['value' => ThesaurusInterface::TYPE_EXPANSION, 'label' => __('Expansion')],
        ];

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Type')]);

        $fieldset->addField(
            'type',
            'select',
            [
                'name' => 'type',
                'label' => __('Thesaurus Type'),
                'title' => __('Thesaurus Type'),
                'values' => $thesaurusTypes,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
