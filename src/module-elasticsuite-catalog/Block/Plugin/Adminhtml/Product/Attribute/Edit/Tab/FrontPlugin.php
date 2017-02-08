<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Plugin\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\CatalogSearch\Model\Source\Weight;
use Magento\Framework\Data\Form;
use Magento\Framework\Registry;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Catalog\Api\Data\EavAttributeInterface;

/**
 * Plugin that happend custom fields dedicated to search configuration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FrontPlugin
{
    /**
     * @var array
     */
    private $movedFields = [
        'is_searchable',
        'is_visible_in_advanced_search',
        'is_filterable',
        'is_filterable_in_search',
        'used_for_sort_by',
        'search_weight',
    ];

    /**
     * @var Weight
     */
    private $weightSource;

    /**
     * @var Yesno
     */
    private $booleanSource;

    /**
     * Class constructor
     *
     * @param Yesno    $booleanSource The YesNo source.
     * @param Weight   $weightSource  Weight source.
     * @param Registry $registry      Core registry.
     */
    public function __construct(Yesno $booleanSource, Weight $weightSource, Registry $registry)
    {
        $this->weightSource  = $weightSource;
        $this->booleanSource = $booleanSource;
        $this->coreRegistry  = $registry;
    }

    /**
     * Append ES specifics fields into the attribute edit store front tab.
     *
     * @param Front    $subject The StoreFront tab
     * @param \Closure $proceed The parent function
     * @param Form     $form    The form
     *
     * @return Front
     */
    public function aroundSetForm(Front $subject, \Closure $proceed, Form $form)
    {
        $block = $proceed($form);

        $fieldset = $this->createFieldset($form, $subject);

        $this->moveOrginalFields($form);
        $this->addSearchFields($fieldset);
        $this->addAutocompleteFields($fieldset);
        $this->addFacetFields($fieldset);

        $this->appendSliderDisplayRelatedFields($form, $subject);

        if ($this->getAttribute()->getAttributeCode() == 'name') {
            $form->getElement('is_searchable')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setValue(1);
        }

        $this->appendFieldsDependency($form, $subject);

        return $block;
    }

    /**
     * Return the current edit attribute.
     *
     * @return EavAttributeInterface
     */
    private function getAttribute()
    {
        return $this->coreRegistry->registry('entity_attribute');
    }

    /**
     * Append the "Search Configuration" fieldset to the tab.
     *
     * @param Form  $form    Target form.
     * @param Front $subject Target tab.
     *
     * @return Fieldset
     */
    private function createFieldset(Form $form, Front $subject)
    {
        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_fieldset',
            [
                'legend'      => __('Search Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'front_fieldset'
        );

        return $fieldset;
    }

    /**
     * Move original fields to the new fieldset.
     *
     * @param Form $form Form
     *
     * @return FrontPlugin
     */
    private function moveOrginalFields(Form $form)
    {
        $originalFieldset = $form->getElement('front_fieldset');
        $targetFieldset   = $form->getElement('elasticsuite_catalog_attribute_fieldset');

        foreach ($this->movedFields as $elementId) {
            $element = $form->getElement($elementId);
            if ($element) {
                $originalFieldset->removeField($elementId);
                $targetFieldset->addElement($element);
            }
        }

        return $this;
    }

    /**
     * Append autocomplete related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addAutocompleteFields(Fieldset $fieldset)
    {
        $fieldset->addField(
            'is_used_in_autocomplete',
            'select',
            [
                'name'   => 'is_used_in_autocomplete',
                'label'  => __('Used in autocomplete'),
                'values' => $this->booleanSource->toOptionArray(),
            ],
            'is_used_in_spellcheck'
        );

        $fieldset->addField(
            'is_displayed_in_autocomplete',
            'select',
            [
                'name'   => 'is_displayed_in_autocomplete',
                'label'  => __('Display in autocomplete'),
                'values' => $this->booleanSource->toOptionArray(),
            ],
            'is_used_in_autocomplete'
        );

        return $this;
    }

    /**
     * Append faceting related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addFacetFields(Fieldset $fieldset)
    {
        $fieldset->addField(
            'facet_min_coverage_rate',
            'text',
            [
                'name'  => 'facet_min_coverage_rate',
                'label' => __('Facet coverage rate'),
                'class' => 'validate-digits validate-digits-range digits-range-0-100',
                'value' => '90',
                'note'  => __('Ex: Brand facet will be displayed only if 90% of the product have a brand.'),
            ],
            'is_filterable_in_search'
        );

        $fieldset->addField(
            'facet_max_size',
            'text',
            [
                'name'  => 'facet_max_size',
                'label' => __('Facet max. size'),
                'class' => 'validate-digits validate-greater-than-zero',
                'value' => '10',
                'note'  => __('Max number of values returned by a facet query.'),
            ],
            'facet_min_coverage_rate'
        );

        $fieldset->addField(
            'facet_sort_order',
            'select',
            [
                'name'   => 'facet_sort_order',
                'label'  => __('Facet sort order'),
                'values' => [
                    ['value' => BucketInterface::SORT_ORDER_COUNT, 'label' => __('Result count')],
                    ['value' => BucketInterface::SORT_ORDER_MANUAL, 'label' => __('Admin sort')],
                    ['value' => BucketInterface::SORT_ORDER_TERM, 'label' => __('Name')],
                    ['value' => BucketInterface::SORT_ORDER_RELEVANCE, 'label' => __('Relevance')],
                ],
            ],
            'facet_max_size'
        );

        return $this;
    }

    /**
     * Append spellchecking related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addSearchFields(Fieldset $fieldset)
    {
        $fieldset->addField(
            'search_weight',
            'select',
            [
                'name' => 'search_weight',
                'label' => __('Search Weight'),
                'values' => $this->weightSource->getOptions(),
            ],
            'is_searchable'
        );

        $fieldset->addField(
            'is_used_in_spellcheck',
            'select',
            [
                'name'   => 'is_used_in_spellcheck',
                'label'  => __('Used in spellcheck'),
                'values' => $this->booleanSource->toOptionArray(),
            ],
            'search_weight'
        );

        return $this;
    }

    /**
     * Append the "Slider Display Configuration" fieldset to the tab.
     *
     * @param Form  $form    Target form.
     * @param Front $subject Target tab.
     *
     * @return Fieldset
     */
    private function createDisplayFieldset(Form $form, Front $subject)
    {
        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_display_fieldset',
            [
                'legend'      => __('Slider Display Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'elasticsuite_catalog_attribute_fieldset'
        );

        return $fieldset;
    }

    /**
     * Append display related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addDisplayFields(Fieldset $fieldset)
    {
        $fieldset->addField(
            'display_pattern',
            'text',
            [
                'name'  => 'display_pattern',
                'label' => __('Display pattern'),
                'note'  => __('A pattern like %s UNIT where %s is the value. Eg : $%s => $20 or %s € => 20 €'),
            ]
        );

        $fieldset->addField(
            'display_precision',
            'text',
            [
                'name'  => 'display_precision',
                'label' => __('Display Precision'),
                'class' => 'validate-digits',
                'value' => '0',
                'note'  => __('The number of digits to use for precision when displaying.'),
            ],
            'display_pattern'
        );

        return $this;
    }

    /**
     * Append slider display related fields
     *
     * @param Form  $form    The form
     * @param Front $subject The StoreFront tab
     *
     * @return FrontPlugin
     */
    private function appendSliderDisplayRelatedFields($form, $subject)
    {
        $attribute          = $this->getAttribute();
        $isAttributeDecimal = $attribute->getBackendType() == 'decimal' || $attribute->getFrontendClass() == 'validate-number';

        if ($isAttributeDecimal && ($attribute->getFrontendInput() !== 'price')) {
            $displayFieldset = $this->createDisplayFieldset($form, $subject);
            $this->addDisplayFields($displayFieldset);
        }

        return $this;
    }

    /**
     * Manage dependency between fields.
     *
     * @param Front $subject The StoreFront tab
     *
     * @return FrontPlugin
     */
    private function appendFieldsDependency($subject)
    {
        /** @var \Magento\Backend\Block\Widget\Form\Element\Dependence $dependencyBlock */
        $dependencyBlock = $subject->getChildBlock('form_after');

        if ($dependencyBlock) {
            $dependencyBlock
                ->addFieldMap('is_displayed_in_autocomplete', 'is_displayed_in_autocomplete')
                ->addFieldMap('is_filterable_in_search', 'is_filterable_in_search')
                ->addFieldDependence('is_displayed_in_autocomplete', 'is_filterable_in_search', '1');
        }

        return $this;
    }
}
