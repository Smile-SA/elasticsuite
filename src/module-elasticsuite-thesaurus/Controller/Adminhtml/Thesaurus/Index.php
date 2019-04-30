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
namespace Smile\ElasticsuiteThesaurus\Controller\Adminhtml\Thesaurus;

use Smile\ElasticsuiteThesaurus\Controller\Adminhtml\AbstractThesaurus as ThesaurusController;

/**
 * Thesaurus index grid controller
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Index extends ThesaurusController
{
    /**
     * Render Thesaurus grid
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Thesaurus'));
        $resultPage->addBreadcrumb(__('Thesaurus'), __('Thesaurus'));

        return $resultPage;
    }
}
