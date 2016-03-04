<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Block\Plugin\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Front;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form;
use Magento\Framework\Registry;

/**
 * Plugin that happend custom fields dedicated to search configuration
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FrontPlugin
{
    /**
     * @var Yesno
     */
    private $yesNo;

    /**
     * Class constructor
     *
     * @param Yesno    $yesNo    The YesNo source
     * @param Registry $registry Core registry
     */
    public function __construct(Yesno $yesNo, Registry $registry)
    {
        $this->yesNo        = $yesNo;
        $this->coreRegistry = $registry;
    }

    /**
     * @param Front    $subject The StoreFront tab
     * @param \Closure $proceed The parent function
     * @param Form     $form    The form
     *
     * @return Front
     */
    public function aroundSetForm(Front $subject, \Closure $proceed, Form $form)
    {
        $block = $proceed($form);

        $attributeObject = $this->coreRegistry->registry('entity_attribute');

        $yesnoSource = $this->yesNo->toOptionArray();

        $originalFieldSet = $form->getElement('front_fieldset');

        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_fieldset',
            [
                'legend'      => __('Search Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'front_fieldset'
        );

        $elementsToMove = ['is_searchable', 'is_visible_in_advanced_search', 'is_filterable', 'is_filterable_in_search',
            'used_for_sort_by', 'search_weight', ];

        foreach ($elementsToMove as $elementId) {
            $element = $form->getElement($elementId);
            if ($element) {
                $originalFieldSet->removeField($elementId);
                $fieldset->addElement($element);
            }
        }

        $fieldset->addField(
            'is_used_in_autocomplete',
            'select',
            [
                'name'   => 'is_used_in_autocomplete',
                'label'  => __('Used in autocomplete'),
                'values' => $yesnoSource,
            ],
            'search_weight'
        );

        $fieldset->addField(
            'is_displayed_in_autocomplete',
            'select',
            [
                'name'   => 'is_displayed_in_autocomplete',
                'label'  => __('Display in autocomplete'),
                'values' => $yesnoSource,
            ],
            'is_used_in_autocomplete'
        );

        $fieldset->addField(
            'is_used_in_spellcheck',
            'select',
            [
                'name'   => 'is_used_in_spellcheck',
                'label'  => __('Used in spellcheck'),
                'values' => $yesnoSource,
            ],
            'is_displayed_in_autocomplete'
        );

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
            'is_fuzziness_enabled'
        );

        $fieldset->addField(
            'facet_max_size',
            'text',
            [
                'name'  => 'facet_max_size',
                'label' => __('Facet max. size'),
                'class' => 'validate-digits validate-greater-than-zero',
                'value' => '10',
                'note'  => implode(
                    '</br>',
                    [
                        __('Max number of values returned by a facet query.'),
                    ]
                ),
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
                    [
                        'value' => \Smile\ElasticSuiteCore\Search\Request\BucketInterface::SORT_ORDER_COUNT,
                        'label' => __('Result count'),
                    ],
                    [
                        'value' => \Smile\ElasticSuiteCore\Search\Request\BucketInterface::SORT_ORDER_MANUAL,
                        'label' => __('Admin sort'),
                    ],
                    [
                        'value' => \Smile\ElasticSuiteCore\Search\Request\BucketInterface::SORT_ORDER_TERM,
                        'label' => __('Name'),
                    ],
                    [
                        'value' => \Smile\ElasticSuiteCore\Search\Request\BucketInterface::SORT_ORDER_RELEVANCE,
                        'label' => __('Relevance'),
                    ],
                ],
            ],
            'facet_max_size'
        );

        $subject->getChildBlock('form_after')
            ->addFieldMap('is_used_in_autocomplete', 'is_used_in_autocomplete')
            ->addFieldMap('is_displayed_in_autocomplete', 'is_displayed_in_autocomplete')
            ->addFieldMap('is_used_in_spellcheck', 'is_used_in_spellcheck')
            ->addFieldMap('facet_min_coverage_rate', 'facet_min_coverage_rate')
            ->addFieldMap('facet_max_size', 'facet_max_size')
            ->addFieldMap('facet_sort_order', 'facet_sort_order')
            ->addFieldMap('is_filterable', 'is_filterable')
            ->addFieldMap('facet_sort_order', 'facet_sort_order')
            ->addFieldDependence('is_used_in_autocomplete', 'searchable', '1')
            ->addFieldDependence('is_displayed_in_autocomplete', 'searchable', '1')
            ->addFieldDependence('is_used_in_spellcheck', 'searchable', '1')
            ->addFieldDependence('facet_min_coverage_rate', 'searchable', '1')
            ->addFieldDependence('facet_max_size', 'searchable', '1')
            ->addFieldDependence('facet_sort_order', 'searchable', '1');

        if ($attributeObject->getAttributeCode() == 'name') {
            $form->getElement('is_searchable')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setValue(1);
        }

        return $block;
    }
}
