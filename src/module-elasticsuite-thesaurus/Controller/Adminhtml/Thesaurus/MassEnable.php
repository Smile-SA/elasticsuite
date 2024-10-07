<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Controller\Adminhtml\Thesaurus;

use Smile\ElasticsuiteThesaurus\Controller\Adminhtml\AbstractThesaurus as ThesaurusController;

/**
 * Massive enable action for Thesaurus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class MassEnable extends ThesaurusController
{
    /**
     * Enable selected Thesaurus
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $thesauriIds = $this->getRequest()->getParam('thesauri');
        if (!is_array($thesauriIds)) {
            $this->messageManager->addError(__('Please select thesaurus to enable.'));

            return $resultRedirect->setPath('*/*/index');
        }

        try {
            foreach ($thesauriIds as $thesaurusId) {
                $model = $this->thesaurusFactory->create();
                $model->load($thesaurusId);
                if (!$model->getThesaurusId()) {
                    $this->messageManager->addError(__('This thesaurus no longer exists.'));

                    return $resultRedirect->setPath('*/*/index');
                }
                $this->thesaurusRepository->enable($model);
            }
            $this->messageManager->addSuccess(__('Total of %1 thesaurus were enabled.', count($thesauriIds)));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
