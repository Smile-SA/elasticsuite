<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Plugin\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\CatalogSearch\Model\Source\Weight;
use Magento\Framework\Data\Form;
use Magento\Framework\Registry;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterBooleanLogic;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterSortOrder;
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
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterSortOrder
     */
    private $filterSortOrder;

    /**
     * @var FilterBooleanLogic
     */
    private $filterBooleanLogic;

    /**
     * Class constructor
     *
     * @param Yesno              $booleanSource      The YesNo source.
     * @param Weight             $weightSource       Weight source.
     * @param Registry           $coreRegistry       Core registry.
     * @param FilterSortOrder    $filterSortOrder    Filter Sort Order.
     * @param FilterBooleanLogic $filterBooleanLogic Filter boolean logic source model.
     */
    public function __construct(
        Yesno $booleanSource,
        Weight $weightSource,
        Registry $coreRegistry,
        FilterSortOrder $filterSortOrder,
        FilterBooleanLogic $filterBooleanLogic
    ) {
        $this->weightSource    = $weightSource;
        $this->booleanSource   = $booleanSource;
        $this->coreRegistry    = $coreRegistry;
        $this->filterSortOrder = $filterSortOrder;
        $this->filterBooleanLogic = $filterBooleanLogic;
    }

    /**
     * Append ES specifics fields into the attribute edit store front tab.
     *
     * @param Front $subject The StoreFront tab
     * @param Front $result  Result
     * @param Form  $form    The form
     *
     * @return Front
     */
    public function afterSetForm(Front $subject, Front $result, Form $form)
    {
        $fieldset = $this->createFieldset($form, $subject);

        $this->moveOrginalFields($form);
        $this->addSearchFields($fieldset);
        $this->addAutocompleteFields($fieldset);
        $this->addFacetFields($fieldset);
        $this->addSortFields($fieldset);
        $this->addRelNofollowFields($fieldset);
        $this->appendSliderDisplayRelatedFields($form, $subject);

        if ($this->getAttribute()->getAttributeCode() == 'name') {
            $form->getElement('is_searchable')->setDisabled(1);
        }

        if (($this->getAttribute()->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean')
            || ($this->getAttribute()->getBackendType() == 'int')
            || ($this->getAttribute()->getFrontendClass() == 'validate-digits')
            || ($this->getAttribute()->getBackendType() == 'decimal' || $this->getAttribute()->getFrontendClass() == 'validate-number')
            || (in_array($this->getAttribute()->getFrontendInput(), ['select', 'multiselect'])
                || $this->getAttribute()->getSourceModel() != '')
        ) {
            $this->addIncludeZeroFalseField($fieldset);
        }

        $this->appendFieldsDependency($subject);

        return $result;
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

        $fieldset->addClass('es-esfeature__logo');

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

        $filterableFieldNote = __('Can be used only with catalog input type Text field, Dropdown, Multiple Select and Price.');
        if ($form->getElement('is_filterable')) {
            $form->getElement('is_filterable')->addData(['note' => $filterableFieldNote]);
        }

        if ($form->getElement('is_filterable_in_search')) {
            $form->getElement('is_filterable_in_search')->addData(['note' => $filterableFieldNote]);
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
            'is_displayed_in_autocomplete',
            'select',
            [
                'name'   => 'is_displayed_in_autocomplete',
                'label'  => __('Display in autocomplete'),
                'values' => $this->booleanSource->toOptionArray(),
            ],
            'is_used_in_spellcheck'
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
                'class' => 'validate-digits validate-zero-or-greater',
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
                'values' => $this->filterSortOrder->toOptionArray(),
            ],
            'facet_max_size'
        );

        $booleanLogicOptions    = $this->filterBooleanLogic->toOptionArray();
        $booleanLogicNote       = __(
            // phpcs:ignore Generic.Files.LineLength
            'When several values are selected in a facet/filter, the default behavior is to combine them with a logical OR ("red" OR "blue").'
            . ' But a logical AND can be handy for some attributes ("egg free" AND "gluten free", "waterproof AND lightweight AND warm").'
        );

        $booleanLogicNote       = __($booleanLogicNote);
        $fieldset->addField(
            'facet_boolean_logic',
            'select',
            [
                'name'   => 'facet_boolean_logic',
                'label'  => __('Facet internal logic'),
                'values' => $booleanLogicOptions,
                'note'   => $booleanLogicNote,
            ],
            'facet_sort_order'
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
     * Append sorting related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addSortFields(Fieldset $fieldset)
    {
        $sortFieldsOptions = [
            ['value' => '_first', 'label' => __("First")],
            ['value' => '_last',  'label' => __("Last")],
        ];

        $fieldset->addField(
            'sort_order_asc_missing',
            'select',
            [
                'name'   => 'sort_order_asc_missing',
                'label'  => __('Sort products without value when sorting ASC'),
                'values' => $sortFieldsOptions,
                'note'   => __('How the products which are missing values for this attribute should be treated when using it to sort.'),
            ],
            'used_for_sortby'
        );

        $fieldset->addField(
            'sort_order_desc_missing',
            'select',
            [
                'name'   => 'sort_order_desc_missing',
                'label'  => __('Sort products without value when sorting DESC'),
                'values' => $sortFieldsOptions,
                'note'   => __('How the products which are missing values for this attribute should be treated when using it to sort.'),
            ],
            'sort_order_asc_missing'
        );

        return $this;
    }

    /**
     * Append rel tag related fields.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addRelNofollowFields(Fieldset $fieldset)
    {
        $fieldset->addField(
            'is_display_rel_nofollow',
            'select',
            [
                'name'   => 'is_display_rel_nofollow',
                'label'  => __('Add rel="nofollow" to filter links in Layered Navigation'),
                'values' => $this->booleanSource->toOptionArray(),
                'note'   => __(
                    'Adds HTML attribute rel with value "nofollow" to all filter links of current attribute in Layered Navigation.'
                ),
            ],
            'is_filterable'
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
     * Add field allowing to configure if zero/false values should be indexed or ignored.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addIncludeZeroFalseField(Fieldset $fieldset)
    {
        $includeZeroFalseNote = __(
            // phpcs:ignore Generic.Files.LineLength
            'If set to Yes, zero (integer or numeric attribute) or false (boolean attribute) values will be indexed in the search engine (default is No).'
            . ' Also applies to source model keys/values of Dropdown/Multiple Select attributes.'
        );
        $fieldset->addField(
            'include_zero_false_values',
            'select',
            [
                'name'   => 'include_zero_false_values',
                'label'  => __('Include zero or false values'),
                'values' => $this->booleanSource->toOptionArray(),
                // phpcs:ignore Generic.Files.LineLength
                'note'   => $includeZeroFalseNote,
            ],
            'used_for_sortby'
        );

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
                ->addFieldMap('is_filterable', 'is_filterable')
                ->addFieldMap('is_filterable_in_search', 'is_filterable_in_search')
                ->addFieldMap('is_searchable', 'is_searchable')
                ->addFieldMap('is_used_in_spellcheck', 'is_used_in_spellcheck')
                ->addFieldMap('used_for_sort_by', 'used_for_sort_by')
                ->addFieldMap('sort_order_asc_missing', 'sort_order_asc_missing')
                ->addFieldMap('sort_order_desc_missing', 'sort_order_desc_missing')
                ->addFieldMap('is_display_rel_nofollow', 'is_display_rel_nofollow')
                ->addFieldDependence('is_displayed_in_autocomplete', 'is_filterable_in_search', '1')
                ->addFieldDependence('is_used_in_spellcheck', 'is_searchable', '1')
                ->addFieldDependence('sort_order_asc_missing', 'used_for_sort_by', '1')
                ->addFieldDependence('sort_order_desc_missing', 'used_for_sort_by', '1')
                ->addFieldDependence('is_display_rel_nofollow', 'is_filterable', '1');
        }

        return $this;
    }
}
