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
 * Massive deletion action for Thesaurus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class MassDelete extends ThesaurusController
{
    /**
     * Delete a Thesaurus
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $thesauriIds = $this->getRequest()->getParam('thesauri');
        if (!is_array($thesauriIds)) {
            $this->messageManager->addError(__('Please select searches.'));

            return $resultRedirect->setPath('*/*/index');
        }

        try {
            foreach ($thesauriIds as $searchId) {
                $model = $this->thesaurusFactory->create();
                $model->load($searchId);
                if (!$model->getThesaurusId()) {
                    $this->messageManager->addError(__('This thesaurus no longer exists.'));

                    return $resultRedirect->setPath('*/*/index');
                }
                $this->thesaurusRepository->delete($model);
            }
            $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($thesauriIds)));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
