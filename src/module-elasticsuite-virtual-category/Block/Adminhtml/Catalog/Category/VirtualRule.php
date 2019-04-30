<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Block\Adminhtml\Catalog\Category;

use \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler;

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
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler
     */
    private $readHandler;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Context      $context     Block context.
     * @param \Magento\Framework\Data\FormFactory $formFactory Form factory.
     * @param \Magento\Framework\Registry         $registry    Registry.
     * @param ReadHandler                         $readHandler Rule read handler.
     * @param array                               $data        Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        ReadHandler $readHandler,
        array $data = []
    ) {
        $this->formFactory  = $formFactory;
        $this->registry     = $registry;
        $this->readHandler  = $readHandler;

        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _toHtml()
    {
        return $this->getForm()->toHtml();
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

        $virtualRule = $this->getVirtualRule($this->getCategory());

        $virtualRuleField->setValue($virtualRule);
        $virtualRuleRenderer = $this->getLayout()->createBlock('Smile\ElasticsuiteCatalogRule\Block\Product\Conditions');

        $urlParams = ['category_id' => $this->getCategory()->getId()];

        $virtualRuleRenderer->addData(['url_params' => $urlParams]);
        $virtualRuleField->setRenderer($virtualRuleRenderer);

        return $form;
    }

    /**
     * Load virtual rule of a category. Can occurs when data is set directly as array to the category
     * (Eg. when the category edit form is submitted with error and populated from session data).
     *
     * @param CategoryInterface $category Category
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    private function getVirtualRule(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        if (!is_object($category->getVirtualRule())) {
            $category = clone $category; // No side effect on category which is in registry.
            $this->readHandler->execute($category);
        }

        return $category->getVirtualRule();
    }
}
