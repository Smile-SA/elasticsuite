<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Block\Adminhtml\Optimizer;

use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;

/**
 * Create the virtual rule edit field in the category edit form.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class RuleCondition extends \Magento\Backend\Block\AbstractBlock
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Context                   $context     Block context.
     * @param \Magento\Framework\Data\FormFactory              $formFactory Form factory.
     * @param \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory Rule factory.
     * @param \Magento\Framework\Registry                      $registry    Registry.
     * @param array                                            $data        Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->formFactory = $formFactory;
        $this->ruleFactory = $ruleFactory;
        $this->registry    = $registry;

        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _toHtml()
    {
        return $this->escapeJsQuote($this->getForm()->toHtml());
    }

    /**
     * Get Optimizer
     *
     * @return OptimizerInterface
     */
    private function getOptimizer()
    {
        return $this->registry->registry('current_optimizer');
    }

    /**
     * Create the form containing the rule field.
     *
     * @return \Magento\Framework\Data\Form
     */
    private function getForm()
    {
        $rule = $this->ruleFactory->create();

        if ($this->getOptimizer() && $this->getOptimizer()->getRuleCondition()) {
            $rule = $this->getOptimizer()->getRuleCondition();
        }

        $form = $this->formFactory->create();
        $form->setHtmlId('rule_condition');

        $ruleConditionField = $form->addField(
            'rule_condition',
            'text',
            ['name' => 'rule_condition', 'label' => __('Rule conditions'), 'container_id' => 'rule_condition']
        );

        $ruleConditionField->setValue($rule);
        $ruleConditionRenderer = $this->getLayout()->createBlock('Smile\ElasticsuiteCatalogRule\Block\Product\Conditions');
        $ruleConditionField->setRenderer($ruleConditionRenderer);

        return $form;
    }
}
