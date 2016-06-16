<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\Catalog\Category;

/**
 * Create the virtual rule edit field in the category edit form.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class VirtualRule extends \Magento\Backend\Block\AbstractBlock
{
    /**
     * @var \Magento\Catalog\Model\Category\DataProvider
     */
    private $dataProvider;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Context      $context     Block context.
     * @param \Magento\Framework\Data\FormFactory $formFactory Form factory.
     * @param \Magento\Framework\Registry         $registry    Registry.
     * @param array                               $data        Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->formFactory  = $formFactory;
        $this->registry     = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _toHtml()
    {
        return $this->escapeJsQuote($this->getForm()->toHtml());
    }

    /**
     * Returns the currently edited category.
     *
     * @return \Magento\Catalog\Model\Category
     */
    private function getCategory()
    {
        return $this->registry->registry('category');
    }

    /**
     * Create the form containing the virtual rule field.
     *
     * @return \Magento\Framework\Data\Form
     */
    private function getForm()
    {
        $form = $this->formFactory->create();
        $form->setHtmlId('virtual_rule');

        $virtualRuleField    = $form->addField(
            'virtual_rule',
            'text',
            ['name' => 'virtual_rule', 'label' => __('Virtual rule'), 'container_id' => 'virtual_rule']
        );

        $virtualRuleField->setValue($this->getCategory()->getVirtualRule());
        $virtualRuleRenderer = $this->getLayout()->createBlock('Smile\ElasticsuiteCatalogRule\Block\Product\Conditions');
        $virtualRuleField->setRenderer($virtualRuleRenderer);

        return $form;
    }
}
