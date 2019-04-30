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
 * Renderer for synonyms inputs
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Synonyms extends AbstractRenderer
{
    /**
     * @var integer Size of the textarea to display
     */
    protected $textAreaColsNumber = 120;

    /**
     * Initialise form fields
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->addColumn('term_id', ['label' => '']);
        $this->addColumn(
            'values',
            ['label' => __('Synonym terms')]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Synonym');

        parent::_construct();
    }
}
