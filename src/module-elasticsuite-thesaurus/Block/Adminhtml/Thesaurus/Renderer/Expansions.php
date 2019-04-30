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
namespace Smile\ElasticsuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer;

/**
 * Renderer for "bag of words" elements
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Expansions extends AbstractRenderer
{
    /**
     * Initialise form fields
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->addColumn('term_id', ['label' => '']);
        $this->addColumn('reference_term', ['label' => __('Reference Term')]);
        $this->addColumn('values', ['label' => __('Expansion terms')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Expansion');

        parent::_construct();
    }
}
