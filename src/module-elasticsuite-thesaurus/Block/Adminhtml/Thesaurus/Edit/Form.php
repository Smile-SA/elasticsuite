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
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus Edit form
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var Store
     */
    private $systemStore;

    /**
     * @var Yesno
     */
    private $booleanSource;

    /**
     * Constructor
     *
     * @param Context                     $context       Application context
     * @param \Magento\Framework\Registry $registry      The registry
     * @param FormFactory                 $formFactory   Form factory
     * @param Store                       $systemStore   Store Provider
     * @param Yesno                       $booleanSource Boolean Input Source Model
     * @param array                       $data          Object data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        Yesno $booleanSource,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->booleanSource = $booleanSource;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init Form properties.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('thesaurus_edit_form');
        $this->setTitle(__('Edit a Thesaurus'));
    }

    /**
     * Prepare form fields.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return Form
     */
    protected function _prepareForm()
    {
        $model = $this->getModel();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('thesaurus_id', 'hidden', ['name' => 'thesaurus_id']);
        }

        if ($model->getType()) {
            $fieldset->addField('type', 'hidden', ['name' => 'type']);
        }

        $this->initBaseFields($fieldset);
        $this->initTypeFields($fieldset, $model);

        $form->setValues($model->getData());

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Init base fields :
     *  - thesaurus name
     *  - store id
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset The fieldset
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Edit\Form
     */
    private function initBaseFields($fieldset)
    {
        $fieldset->addField(
            'name',
            'text',
            [
                'name'     => 'name',
                'label'    => __('Thesaurus Name'),
                'title'    => __('Thesaurus Name'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'name'     => 'is_active',
                'label'    => __('Active'),
                'title'    => __('Active'),
                'values'   => $this->booleanSource->toOptionArray(),
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'multiselect',
                [
                    'name'     => 'stores[]',
                    'label'    => __('Store'),
                    'title'    => __('Store'),
                    'values'   => $this->systemStore->getStoreValuesForForm(false, true),
                    'required' => true,
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );

            $field->setRenderer($renderer);
        }

        return $this;
    }

    /**
     * Init type fields : fields are different according to thesaurus type
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset     $fieldset The fieldset
     * @param \Smile\ElasticsuiteThesaurus\Model\Thesaurus|null $model    Current Thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Edit\Form
     */
    private function initTypeFields($fieldset, $model)
    {
        if ($model->getType() === ThesaurusInterface::TYPE_EXPANSION) {
            $this->addExpansionFields($fieldset, $model);
        } elseif ($model->getType() === ThesaurusInterface::TYPE_SYNONYM) {
            $this->addSynonymFields($fieldset, $model);
        }

        return $this;
    }

    /**
     * Adding expansion-related fields
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset     $fieldset The fieldset
     * @param \Smile\ElasticsuiteThesaurus\Model\Thesaurus|null $model    Current Thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Edit\Form
     */
    private function addExpansionFields($fieldset, $model)
    {
        $form = $fieldset->getForm();

        /* @var $bagRenderer \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer\Expansions */
        $bagRenderer = $this->getLayout()->createBlock(
            'Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer\Expansions'
        )->setForm($fieldset->getForm());

        $fieldset = $form->addFieldset('bag_of_words_fieldset', ['legend' => __('Bag of words')]);
        $fieldset->addField('bag_of_words', 'note', []);

        $form->getElement('bag_of_words_fieldset')
            ->setName('terms_relations')
            ->setValue($model->getTermsData())
            ->setRenderer($bagRenderer);
        $form->getElement('bag_of_words_fieldset')->getRenderer()->setValues($model->getTermsData());

        return $this;
    }

    /**
     * Adding synonym-related fields
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset     $fieldset The fieldset
     * @param \Smile\ElasticsuiteThesaurus\Model\Thesaurus|null $model    Current Thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Edit\Form
     */
    private function addSynonymFields($fieldset, $model)
    {
        $form = $fieldset->getForm();

        /* @var $synonymsRenderer \Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer\Synonyms */
        $synonymsRenderer = $this->getLayout()->createBlock(
            'Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer\Synonyms'
        )->setForm($fieldset->getForm());

        $fieldset = $form->addFieldset('synonyms_fieldset', ['legend' => __('Synonyms')]);
        $fieldset->addField('synonyms', 'note', []);
        $form->getElement('synonyms_fieldset')
            ->setName('terms_relations')
            ->setValue($model->getTermsData())
            ->setRenderer($synonymsRenderer);
        $form->getElement('synonyms_fieldset')->getRenderer()->setValues($model->getTermsData());

        return $this;
    }

    /**
     * Retrieve current model if any
     *
     * @return \Smile\ElasticsuiteThesaurus\Model\Thesaurus
     */
    private function getModel()
    {
        /* @var $model \Smile\ElasticsuiteThesaurus\Model\Thesaurus */
        $model = $this->_coreRegistry->registry('current_thesaurus');

        return $model;
    }
}
