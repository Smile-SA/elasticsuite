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
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterBooleanLogic;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterSortOrder;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Catalog\Api\Data\EavAttributeInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

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
        'is_searchable' => 'elasticsuite_catalog_attribute_fieldset',
        'is_visible_in_advanced_search' => 'elasticsuite_catalog_attribute_fieldset',
        'is_filterable' => 'elasticsuite_catalog_attribute_navigation_fieldset',
        'is_filterable_in_search' => 'elasticsuite_catalog_attribute_navigation_fieldset',
        'used_for_sort_by' => 'elasticsuite_catalog_attribute_fieldset',
        'search_weight' => 'elasticsuite_catalog_attribute_fieldset',
        'position' => 'elasticsuite_catalog_attribute_navigation_fieldset',
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Class constructor
     *
     * @param Yesno              $booleanSource      The YesNo source.
     * @param Weight             $weightSource       Weight source.
     * @param Registry           $coreRegistry       Core registry.
     * @param FilterSortOrder    $filterSortOrder    Filter Sort Order.
     * @param FilterBooleanLogic $filterBooleanLogic Filter boolean logic source model.
     * @param UrlInterface       $urlBuilder         Url Builder.
     */
    public function __construct(
        Yesno $booleanSource,
        Weight $weightSource,
        Registry $coreRegistry,
        FilterSortOrder $filterSortOrder,
        FilterBooleanLogic $filterBooleanLogic,
        UrlInterface $urlBuilder
    ) {
        $this->weightSource    = $weightSource;
        $this->booleanSource   = $booleanSource;
        $this->coreRegistry    = $coreRegistry;
        $this->filterSortOrder = $filterSortOrder;
        $this->filterBooleanLogic = $filterBooleanLogic;
        $this->urlBuilder         = $urlBuilder;
    }

    /**
     * Append ES specifics fields into the attribute edit store front tab.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param Front $subject The StoreFront tab
     * @param Front $result  Result
     * @param Form  $form    The form
     *
     * @return Front
     */
    public function afterSetForm(Front $subject, Front $result, Form $form)
    {
        $searchFieldset            = $this->createSearchFieldset($form, $subject);
        $layeredNavigationFieldset = $this->createLayeredNavigationFieldset($form, $subject);
        $advancedFieldset          = $this->createAdvancedFieldset($form, $subject);

        $this->moveOriginalFields($form);
        $this->addSearchFields($searchFieldset);
        $this->addAutocompleteFields($searchFieldset);
        $this->addFacetFields($layeredNavigationFieldset);
        $this->addSortFields($searchFieldset);
        $this->addRelNofollowFields($layeredNavigationFieldset);
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
            $this->addIncludeZeroFalseField($advancedFieldset);
        }

        $this->addDefaultAnalyzer($advancedFieldset);

        if (($this->getAttribute()->getBackendType() == 'varchar')
            || (in_array($this->getAttribute()->getFrontendInput(), ['select', 'multiselect']))
        ) {
            $this->addIsSpannableField($advancedFieldset);
            $this->addDisableNormsField($advancedFieldset);
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
    private function createSearchFieldset(Form $form, Front $subject)
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
     * Append the "Search Configuration" fieldset to the tab.
     *
     * @param Form  $form    Target form.
     * @param Front $subject Target tab.
     *
     * @return Fieldset
     */
    private function createLayeredNavigationFieldset(Form $form, Front $subject)
    {
        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_navigation_fieldset',
            [
                'legend'      => __('Layered Navigation Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'elasticsuite_catalog_attribute_fieldset'
        );

        $fieldset->addClass('es-esfeature__logo');

        return $fieldset;
    }

    /**
     * Append the "Advanced Search Configuration" fieldset to the tab.
     *
     * @param Form  $form    Target form.
     * @param Front $subject Target tab.
     *
     * @return Fieldset
     */
    private function createAdvancedFieldset(Form $form, Front $subject)
    {
        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_advanced_fieldset',
            [
                'legend'      => __('Advanced Elasticsuite Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'elasticsuite_catalog_attribute_navigation_fieldset'
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
    private function moveOriginalFields(Form $form)
    {
        $originalFieldset = $form->getElement('front_fieldset');

        foreach ($this->movedFields as $elementId => $fieldset) {
            $element = $form->getElement($elementId);
            if ($element) {
                $targetFieldset = $form->getElement($fieldset);
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
     * Add field allowing to configure if a field can be used for span queries.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addIsSpannableField(Fieldset $fieldset)
    {
        $isSpannableNote = __(
        // phpcs:ignore Generic.Files.LineLength
            'Default : No. If set to Yes, the engine will try to match the current query string at the beginning of this string.'
            . ' Eg: when enabled on "name", if a customer search for "red dress", the engine will give an higher score to products having'
            . ' a name beginning by "red dress". This requires the Span Match Boost feature to be enabled.'
        );
        $fieldset->addField(
            'is_spannable',
            'select',
            [
                'name'   => 'is_spannable',
                'label'  => __('Use this field for span queries'),
                'values' => $this->booleanSource->toOptionArray(),
                // phpcs:ignore Generic.Files.LineLength
                'note'   => $isSpannableNote,
            ],
            'default_analyzer'
        );

        return $this;
    }

    /**
     * Add field allowing to configure if zero/false values should be indexed or ignored.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addDisableNormsField(Fieldset $fieldset)
    {
        $disableNormsNote = __(
        // phpcs:ignore Generic.Files.LineLength
            'Default : No. By default, the score of a text match in a field will vary according to the field length.'
            . ' Eg: when searching for "dress", a product named "red dress" will have an higher score than a product named'
            . ' "red dress with long sleeves". You can set this to "Yes" to discard this behavior.'
        );
        $fieldset->addField(
            'norms_disabled',
            'select',
            [
                'name'   => 'norms_disabled',
                'label'  => __('Discard the field length for scoring'),
                'values' => $this->booleanSource->toOptionArray(),
                // phpcs:ignore Generic.Files.LineLength
                'note'   => $disableNormsNote,
            ],
            'is_spannable'
        );

        return $this;
    }

    /**
     * Add field allowing to configure if a field can be used for span queries.
     *
     * @param Fieldset $fieldset Target fieldset
     *
     * @return FrontPlugin
     */
    private function addDefaultAnalyzer(Fieldset $fieldset)
    {
        $link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $this->urlBuilder->getUrl('smile_elasticsuite_indices/analysis/index', ['_query' => []]),
            __("Analysis Page")
        );

        $defaultAnalyzerNote = __(
        // phpcs:ignore Generic.Files.LineLength
            'Default : standard. The default analyzer for this field. Should be set to "reference" for SKU-like fields.'
            . ' You can check the %1 screen to view how these analyzers behave.',
            $link
        );

        $config = [
            'name'   => 'default_analyzer',
            'label'  => __('Default Search Analyzer'),
            'values' => [
                ['value' => FieldInterface::ANALYZER_STANDARD, 'label' => __('standard')],
                ['value' => FieldInterface::ANALYZER_REFERENCE, 'label' => __('reference')],
                ['value' => FieldInterface::ANALYZER_EDGE_NGRAM, 'label' => __('standard_edge_ngram')],
            ],
            // phpcs:ignore Generic.Files.LineLength
            'note'   => $defaultAnalyzerNote,
        ];

        if ($this->getAttribute()->getAttributeCode() === "sku") {
            $config['value'] = FieldInterface::ANALYZER_REFERENCE;
        }

        $fieldset->addField(
            'default_analyzer',
            'select',
            $config,
            'is_used_in_spellcheck'
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
                ->addFieldMap('is_spannable', 'is_spannable')
                ->addFieldMap('norms_disabled', 'norms_disabled')
                ->addFieldMap('default_analyzer', 'default_analyzer')
                ->addFieldMap('search_weight', 'search_weight')
                ->addFieldDependence('is_displayed_in_autocomplete', 'is_filterable_in_search', '1')
                ->addFieldDependence('is_used_in_spellcheck', 'is_searchable', '1')
                ->addFieldDependence('is_spannable', 'is_searchable', '1')
                ->addFieldDependence('norms_disabled', 'is_searchable', '1')
                ->addFieldDependence('search_weight', 'is_searchable', '1')
                ->addFieldDependence('default_analyzer', 'is_searchable', '1')
                ->addFieldDependence('sort_order_asc_missing', 'used_for_sort_by', '1')
                ->addFieldDependence('sort_order_desc_missing', 'used_for_sort_by', '1')
                ->addFieldDependence('is_display_rel_nofollow', 'is_filterable', '1');
        }

        return $this;
    }
}
