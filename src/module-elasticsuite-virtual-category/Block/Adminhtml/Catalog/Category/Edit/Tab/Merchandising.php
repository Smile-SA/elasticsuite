<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Edit\Tab;

use Magento\Catalog\Model\Category;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Config\Model\Config\Source\Yesno;

/**
 * Categroy edit merchandising tab form implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Merchandising extends \Magento\Catalog\Block\Adminhtml\Form
{
    /**
     * @var Category|null
     */
    private $category;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $booleanSource;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     *
     * @param \Magento\Backend\Block\Template\Context          $context       Template context.
     * @param \Magento\Framework\Registry                      $registry      Registry (used to read current category)
     * @param \Magento\Framework\Data\FormFactory              $formFactory   Form factory.
     * @param \Magento\Config\Model\Config\Source\Yesno        $booleanSource Data source for boolean fields.
     * @param \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory   Catalog product rule factory.
     * @param array                                            $data          Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $booleanSource,
        \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->booleanSource = $booleanSource;
        $this->ruleFactory   = $ruleFactory;
    }

    /**
     * Return currently edited category
     *
     * @return Category|null
     */
    public function getCategory()
    {
        if (!$this->category) {
            $this->category = $this->_coreRegistry->registry('category');
        }

        return $this->category;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _prepareLayout()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setDataObject($this->getCategory());

        $this->addCategoryMode($form)
             ->addVirtualCategorySettings($form)
             ->addDependenceManager();

        $form->addValues($this->getCategory()->getData());
        $form->setFieldNameSuffix('general');
        $this->setForm($form);

        return parent::_prepareLayout();
    }

    /**
     * Append the category mode selector.
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return \Smile\ElasticSuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Edit\Tab\Merchandising
     */
    private function addCategoryMode(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset('merchandising_category_mode_fieldset', ['legend' => __('Category mode')]);
        $booleanSelectValues = $this->booleanSource->toOptionArray();
        $fieldset->addField(
            'is_virtual_category',
            'select',
            [
                'name' => 'is_virtual_category',
                'label' => __('Virtual category'),
                'values' => $booleanSelectValues,
            ]
        );

        return $this;
    }

    /**
     * Append settings related to a virtual category (category root and rule applied).
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return \Smile\ElasticSuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Edit\Tab\Merchandising
     */
    private function addVirtualCategorySettings(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset('merchandising_virtual_settings_fieldset', ['legend' => __('Virtual category settings')]);

        /* Create the virtual category root selector. */
        $categoryChooserFieldOptions = ['name' => 'virtual_category_root', 'label' => __('Virtual category root')];

        $categoryChooserField = $fieldset->addField('virtual_category_root', 'label', $categoryChooserFieldOptions);

        $categoryChooserRenderer = $this->getLayout()
            ->createBlock('Magento\Catalog\Block\Adminhtml\Category\Widget\Chooser')
            ->setFieldsetId($fieldset->getId())
            ->setConfig(['buttons' => ['open' => __('Select category ...')]]);

        $categoryChooserRenderer->prepareElementHtml($categoryChooserField);

        /* Append the conditions renderer */
        $virtualRuleRenderer = $this->getLayout()->createBlock('Smile\ElasticSuiteCatalogRule\Block\Product\Conditions');
        $virtualRuleField = $fieldset->addField('virtual_rule', 'text', ['name' => 'virtual_rule', 'label' => __('Virtual rule')]);
        $virtualRuleField->setRenderer($virtualRuleRenderer);

        return $this;
    }

    /**
     * Apply depedence manegemnt on the form.
     *
     * Due to the difficulty to manage dependencies between the multiple fieldset we hacked the mechanisms by using a random
     * field container to get things working.
     *
     *  If you remove the virtual_category_root field the associated container (attribute-chooservirtual_category_root-container)
     *  will not exists anymore and you will have to choose a new one to get things working again.
     *
     * @return \Smile\ElasticSuiteVirtualCategory\Block\Adminhtml\Catalog\Category\Edit\Tab\Merchandising
     */
    private function addDependenceManager()
    {
        $dependenceManagerBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence');
        $dependenceManagerBlock->addConfigOptions(['levels_up' => 0])
            ->addFieldMap('is_virtual_category', 'is_virtual_category')
            ->addFieldMap('attribute-chooservirtual_category_root-container', 'attribute-chooservirtual_category_root-container')
            ->addFieldDependence('attribute-chooservirtual_category_root-container', 'is_virtual_category', '1');

        $this->setChild('form_after', $dependenceManagerBlock);

        return $this;
    }
}
