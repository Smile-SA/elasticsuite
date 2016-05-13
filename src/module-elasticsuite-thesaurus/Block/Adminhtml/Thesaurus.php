<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container as GridContainer;

/**
 * Thesaurus Grid container
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Thesaurus extends GridContainer
{
    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/create');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return void
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_controller = 'thesaurus';
        $this->_headerText = __('Thesaurus');
        $this->_addButtonLabel = __('Add New Thesaurus');

        parent::_construct();
    }
}
