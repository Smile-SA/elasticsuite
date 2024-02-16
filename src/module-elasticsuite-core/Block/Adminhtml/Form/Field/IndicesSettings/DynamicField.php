<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Form\Field\IndicesSettings;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Smile\ElasticsuiteCore\Block\Adminhtml\Form\Field\IndicesSettings\Renderer\DynamicColumn;

/**
 * Adminhtml Elasticsuite -> Base Settings -> Indices Settings -> Custom Number of Shards and Replicas per Index field.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class DynamicField extends AbstractFieldArray
{
    /**
     * @var DynamicColumn
     */
    private $dropdownRenderer;

    /**
     * Prepare to render the new field by adding all the needed columns.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'index_type',
            [
                'label' => __('Index type'),
                'renderer' => $this->getDropdownRenderer(),
            ]
        );

        $this->addColumn(
            'custom_number_shards',
            [
                'label' => __('Number of Shards'),
                'class' => 'required-entry',
                'size' => '10',
            ]
        );

        $this->addColumn(
            'custom_number_replicas',
            [
                'label' => __('Number of Replicas'),
                'class' => 'required-entry',
                'size' => '10',
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add custom settings');
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row Row data object.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        $dropdownField = $row->getDropdownField();

        if ($dropdownField !== null) {
            $options['option_' . $this->getDropdownRenderer()->calcOptionHash($dropdownField)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Retrieve index type column renderer.
     *
     * @return DynamicColumn
     * @throws LocalizedException
     */
    private function getDropdownRenderer()
    {
        if (!$this->dropdownRenderer) {
            $this->dropdownRenderer = $this->getLayout()->createBlock(
                DynamicColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->dropdownRenderer->setClass('es-index_type_select admin__control-select');
        }

        return $this->dropdownRenderer;
    }
}
