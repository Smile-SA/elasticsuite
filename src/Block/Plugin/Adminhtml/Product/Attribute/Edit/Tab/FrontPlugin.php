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
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;

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

        $fieldset = $form->addFieldset(
            'elasticsuite_catalog_attribute_fieldset',
            [
                'legend'      => __('Search Configuration'),
                'collapsable' => $subject->getRequest()->has('popup'),
            ],
            'front_fieldset'
        );

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
            'is_snowball_used'
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
                    ['value' => BucketInterface::SORT_ORDER_COUNT, 'label' => __('Result count')],
                    ['value' => BucketInterface::SORT_ORDER_MANUAL, 'label' => __('Admin sort')],
                    ['value' => BucketInterface::SORT_ORDER_TERM, 'label' => __('Name')],
                    ['value' => BucketInterface::SORT_ORDER_RELEVANCE,'label' => __('Relevance')],
                ],
            ],
            'facets_max_size'
        );

        if ($attributeObject->getAttributeCode() == 'name') {
            $form->getElement('is_searchable')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setDisabled(1);
            $form->getElement('is_used_in_autocomplete')->setValue(1);
        }

        return $block;
    }
}
