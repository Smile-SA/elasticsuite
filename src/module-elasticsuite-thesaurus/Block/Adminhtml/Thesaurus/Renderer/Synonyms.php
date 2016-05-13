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
namespace Smile\ElasticSuiteThesaurus\Block\Adminhtml\Thesaurus\Renderer;

/**
 * Renderer for synonyms inputs
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Synonyms extends AbstractRenderer
{
    /**
     * @var int Size of the textarea to display
     */
    protected $textAreaColsNumber = 120;

    /**
     * Initialise form fields
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _construct()
    {
        //@codingStandardsIgnoreEnd
        $this->addColumn('term_id', ['label' => __('')]);
        $this->addColumn(
            'values',
            ['label' => __('Synonym terms')]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Synonym');

        parent::_construct();
    }
}
