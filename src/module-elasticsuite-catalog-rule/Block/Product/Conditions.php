<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Block\Product;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Catalog search rule contribution form element renderer.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Conditions extends Template implements RendererInterface
{
    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $conditions;

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\Rule
     */
    protected $rule;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $elementFactory;

    /**
     * @var AbstractElement
     */
    protected $element;

    /**
     * @var \Magento\Framework\Data\Form\Element\Text
     */
    protected $input;

    /**
     * @var string
     */
    protected $_template = 'product/conditions.phtml';

    /**
     * Block constructor.
     *
     * @param \Magento\Backend\Block\Template\Context          $context        Templating context.
     * @param \Magento\Framework\Data\Form\Element\Factory     $elementFactory Form element factory.
     * @param \Magento\Rule\Block\Conditions                   $conditions     Rule conditions block.
     * @param \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory    Search rule factory.
     * @param array                                            $data           Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Smile\ElasticsuiteCatalogRule\Model\RuleFactory $ruleFactory,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->conditions     = $conditions;
        $this->rule           = $ruleFactory->create();

        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        $this->element = $element;

        return $this->toHtml();
    }

    /**
     * Get URL used to create a new child condition into the rule.
     *
     * @return string
     */
    public function getNewChildUrl()
    {
        $urlParams = [
            'form'         => $this->getElement()->getContainer()->getHtmlId(),
            'element_name' => $this->getElement()->getName(),
        ];

        if (is_array($this->getData('url_params'))) {
            $urlParams = array_merge($urlParams, $this->getData('url_params'));
        }

        return $this->getUrl('catalog_search_rule/product_rule/conditions', $urlParams);
    }

    /**
     * Get currently edited element.
     *
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Retrieve element unique container id.
     *
     * @return string
     */
    public function getHtmlId()
    {
        return $this->getElement()->getContainer()->getHtmlId();
    }

    /**
     * Render HTML of the element using the rule engine.
     *
     * @return string
     */
    public function getInputHtml()
    {
        $this->rule->setElementName($this->element->getName());

        if ($this->element->getValue()) {
            /* Hack : reload in a new instance to have element name set.
             *        can not be done in afterLoad of the backend model
             *        since we do not know yet the form structure
             */
            $conditions = $this->element->getValue();
            if (!is_array($conditions)) {
                $conditions = $conditions->getConditions()->asArray();
            }
            $this->rule->getConditions()->loadArray($conditions);
            $this->element->setRule($this->rule);
        }

        $this->input = $this->elementFactory->create('text');
        $this->input->setRule($this->rule)->setRenderer($this->conditions);

        $this->setConditionFormName($this->rule->getConditions(), $this->getElement()->getContainer()->getHtmlId());

        if (is_array($this->getData('url_params'))) {
            $this->setConditionUrlParams($this->rule->getConditions(), $this->getData('url_params'));
        }

        return $this->input->toHtml();
    }

    /**
     * Set proper form name to rule conditions.
     *
     * @param \Magento\Rule\Model\Condition\AbstractCondition $conditions Rule conditions.
     * @param string                                          $formName   Form Name.
     *
     * @return void
     */
    private function setConditionFormName(\Magento\Rule\Model\Condition\AbstractCondition $conditions, $formName)
    {
        $conditions->setJsFormObject($formName);
        $conditions->setData('form_name', $formName);

        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }

    /**
     * Set proper url params to rule conditions.
     *
     * @param \Magento\Rule\Model\Condition\AbstractCondition $conditions Rule conditions.
     * @param array                                           $urlParams  URL Params.
     *
     * @return void
     */
    private function setConditionUrlParams(\Magento\Rule\Model\Condition\AbstractCondition $conditions, $urlParams)
    {
        $conditions->setUrlParams($urlParams);

        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionUrlParams($condition, $urlParams);
            }
        }
    }
}
