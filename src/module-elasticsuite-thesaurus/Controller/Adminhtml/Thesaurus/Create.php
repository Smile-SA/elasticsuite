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
namespace Smile\ElasticSuiteThesaurus\Controller\Adminhtml\Thesaurus;

use Smile\ElasticSuiteThesaurus\Controller\Adminhtml\AbstractThesaurus as ThesaurusController;

/**
 * Thesaurus creation controller
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Create extends ThesaurusController
{
    /**
     * Render Thesaurus creation screen
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createPage();

        if ($this->getRequest()->getParam("type")) {
            $this->_forward('edit');

            return $resultPage;
        }

        $resultPage->getConfig()->getTitle()->prepend(__('Create Thesaurus'));
        $resultPage->addBreadcrumb(__('Thesaurus'), __('Thesaurus'));

        return $resultPage;
    }
}
