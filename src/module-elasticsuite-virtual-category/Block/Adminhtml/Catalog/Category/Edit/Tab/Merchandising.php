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
use Magento\Config\Model\Config\Source\Yesno;

/**
 * Category edit merchandising tab form implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Merchandising extends \Magento\Catalog\Block\Adminhtml\Form
{
    /**
     * @var integer
     */
    const DEFAULT_PREVIEW_SIZE = 20;

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
     * @var integer
     */
    private $previewSize;

    /**
     * @var \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product\Position
     */
    private $productPositionResource;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                                          $context                 Template context.
     * @param \Magento\Framework\Registry                                                      $registry                Registry (used to read current category)
     * @param \Magento\Framework\Data\FormFactory                                              $formFactory             Form factory.
     * @param \Magento\Config\Model\Config\Source\Yesno                                        $booleanSource           Data source for boolean fields.
     * @param \Smile\ElasticSuiteCatalogRule\Model\RuleFactory                                 $ruleFactory             Catalog product rule factory.
     * @param \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product\Position $productPositionResource Product position loading resource.
     * @param integer                                                                          $previewSize             Preview size.
     * @param array                                                                            $data                    Additional data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $booleanSource,
        \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory,
        \Smile\ElasticSuiteVirtualCategory\Model\ResourceModel\Category\Product\Position $productPositionResource,
        $previewSize = self::DEFAULT_PREVIEW_SIZE,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->booleanSource           = $booleanSource;
        $this->ruleFactory             = $ruleFactory;
        $this->productPositionResource = $productPositionResource;
        $this->previewSize             = $previewSize;
    }

    /**
     * Return currently edited category
     *
     * @return \Magento\Catalog\Model\Category
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
             ->addProductSorter($form)
             ->addDependenceManager();

        $form->addValues($this->getCategory()->getData());
        $form->setFieldNameSuffix('general');

        $this->addFieldRenderers($form);

        $this->setForm($form);

        return parent::_prepareLayout();
    }

    /**
     * Append the category mode selector.
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return $this
     */
    private function addCategoryMode(\Magento\Framework\Data\Form $form)
    {
           $fieldset = $form->addFieldset('merchandising_category_mode_fieldset', ['legend' => __('Category mode')]);

           $booleanSelectValues = $this->booleanSource->toOptionArray();
           $categoryModeFieldOptions = ['name' => 'is_virtual_category', 'label' => __('Virtual category'), 'values' => $booleanSelectValues];
           $fieldset->addField('is_virtual_category', 'select', $categoryModeFieldOptions);

           return $this;
    }

    /**
     * Append settings related to a virtual category (category root and rule applied).
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return $this
     */
    private function addVirtualCategorySettings(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset('merchandising_virtual_settings_fieldset', ['legend' => __('Virtual category settings')]);

        // This field is added to manage fieldset dependence to the "is_virtual_category" field.
        // @see self::addDependenceManager for more additional information.
        $fieldset->addField('virtual_rule_fieldset_visibility_switcher', 'hidden', ['name' => 'virtual_rule_fieldset_visibility_switcher']);

        // Append the virtual rule conditions field.
        $fieldset->addField('virtual_rule', 'text', ['name' => 'virtual_rule', 'label' => __('Virtual rule')]);

        // Create the virtual category root selector field.
        $categoryChooserFieldOptions = ['name' => 'virtual_category_root', 'label' => __('Virtual category root')];
        $fieldset->addField('virtual_category_root', 'label', $categoryChooserFieldOptions);

        return $this;
    }

    /**
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return $this
     */
    private function addProductSorter(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset('merchandising_product_sort_fieldset', ['legend' => __('Preview and sorting')]);

        $fieldset->addField('sorted_products', 'text', ['name' => 'sorted_products']);

        return $this;
    }

    /**
     * Append renderers to the form.
     *
     * Note : This is called AFTER calling $form->addValues since the category chooser field renderer is not a
     *        real renderer and is not applied when the form is rendered but at build time => we need the values are set.
     *
     * @param \Magento\Framework\Data\Form $form Current form.
     *
     * @return $this
     */
    private function addFieldRenderers(\Magento\Framework\Data\Form $form)
    {
        // Append the virtual conditions rule renderer.
        $virtualRuleField    = $form->getElement('virtual_rule');
        $virtualRuleRenderer = $this->getLayout()->createBlock('Smile\ElasticSuiteCatalogRule\Block\Product\Conditions');
        $virtualRuleField->setRenderer($virtualRuleRenderer);

        // Append the virtual category root chooser.
        $categoryChooserField    = $form->getElement('virtual_category_root');
        $categoryChooserRenderer = $this->getLayout()->createBlock('Magento\Catalog\Block\Adminhtml\Category\Widget\Chooser');
        $categoryChooserRenderer->setFieldsetId($form->getElement('merchandising_virtual_settings_fieldset')->getId())
                                ->setConfig(['buttons' => ['open' => __('Select category ...')]]);
        $categoryChooserRenderer->prepareElementHtml($categoryChooserField);

        $productSortField    = $form->getElement('sorted_products');
        $productSortField->setLoadUrl($this->getPreviewUrl())
            ->setFormId('category_edit_form')
            ->setRefreshElements([
                'category_products',
                $form->getElement('is_virtual_category')->getName(),
                $form->getElement('virtual_rule')->getName(),
                $form->getElement('virtual_category_root')->getName(),
            ])
            ->setSavedPositions($this->getProductSavedPositions())
            ->setPageSize($this->previewSize);

        $productSortRenderer = $this->getLayout()->createBlock('Smile\ElasticSuiteCatalog\Block\Adminhtml\Catalog\Product\Form\Renderer\Sort');
        $productSortField->setRenderer($productSortRenderer);

        return $this;
    }

    /**
     * Apply depedence manegemnt on the form.
     *
     * Due to the difficulty to manage dependencies between the multiple fieldset we hacked the mechanisms by using an
     * arbitary chosen dummy field with a predictable id container to get things working.
     *
     * @return $this
     */
    private function addDependenceManager()
    {
        $dependenceManagerBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence');

        $dependenceManagerBlock->addConfigOptions(['levels_up' => 0])
            ->addFieldMap('is_virtual_category', 'is_virtual_category')
            ->addFieldMap('virtual_rule_fieldset_visibility_switcher', 'virtual_rule_fieldset_visibility_switcher')
            ->addFieldDependence('virtual_rule_fieldset_visibility_switcher', 'is_virtual_category', 1);

        $this->setChild('form_after', $dependenceManagerBlock);

        return $this;
    }

    /**
     * Return the product list preview URL.
     *
     * @return string
     */
    private function getPreviewUrl()
    {
        $storeId = $this->getCategory()->getStoreId();

        if ($storeId === 0) {
            $storeId = current(array_filter($this->getCategory()->getStoreIds()));
        }

        $urlParams = ['ajax' => true, 'store' => $storeId];

        return $this->getUrl('virtualcategory/category_virtual/preview', $urlParams);
    }

    /**
     * Load product saved positions for the current category.
     *
     * @return array
     */
    private function getProductSavedPositions()
    {
        return $this->productPositionResource->getProductPositionsByCategory($this->getCategory());
    }
}
