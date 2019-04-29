<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus;

/**
 * Adminhtml Grid for Thesaurus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     * Append custom filter for Store Id
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _preparePage()
    {
        parent::_preparePage();
        $this->getColumnSet()->getChildBlock('store_id')->setFilterConditionCallback([$this, 'filterStoreCondition']);
        $this->getColumnSet()->getChildBlock('thesaurus_terms_summary')->setFilterConditionCallback([$this, 'filterTermsCondition']);
    }

    /**
     * Apply store filter
     *
     * @param \Magento\Framework\Data\Collection        $collection The collection
     * @param \Magento\Backend\Block\Widget\Grid\Column $column     Columns to filter
     */
    protected function filterStoreCondition($collection, $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }

        $collection->addStoreFilter($value);
    }

    /**
     * Apply term filter
     *
     * @param \Magento\Framework\Data\Collection        $collection The collection
     * @param \Magento\Backend\Block\Widget\Grid\Column $column     Columns to filter
     */
    protected function filterTermsCondition($collection, $column)
    {
        if (!($value = $column->getFilter()->getValue())) {
            return;
        }

        $collection->setTermFilter($value);
    }
}
