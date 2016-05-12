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

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticSuiteThesaurus\Api\ThesaurusRepositoryInterface;
use Smile\ElasticSuiteThesaurus\Controller\Adminhtml\AbstractThesaurus as ThesaurusController;
use Smile\ElasticSuiteThesaurus\Model\ThesaurusFactory;

/**
 * Thesaurus edition controller
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Edit extends ThesaurusController
{
    /**
     * Render Thesaurus edition screen
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->createPage();

        $thesaurusId = (int) $this->getRequest()->getParam(ThesaurusInterface::THESAURUS_ID);
        $type        = (string) $this->getRequest()->getParam('type');

        $thesaurus = null;
        $isExistingThesaurus = (bool) $thesaurusId;

        if ($isExistingThesaurus) {
            try {
                $thesaurus = $this->thesaurusRepository->getById($thesaurusId);
                $resultPage->getConfig()->getTitle()->prepend(
                    __('Edit %1 (%2)', $thesaurus->getName(), $thesaurus->getType())
                );
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addException($e, __('Something went wrong while editing the thesaurus.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/index');

                return $resultRedirect;
            }
        }

        if (!$isExistingThesaurus) {
            $thesaurus = $this->thesaurusFactory->create();
            $thesaurus->setType($type);
            $resultPage->getConfig()->getTitle()->prepend(__('New Thesaurus (%1)', $type));
        }

        $this->coreRegistry->register('current_thesaurus', $thesaurus);
        $resultPage->addBreadcrumb(__('Thesaurus'), __('Thesaurus'));

        return $resultPage;
    }
}
