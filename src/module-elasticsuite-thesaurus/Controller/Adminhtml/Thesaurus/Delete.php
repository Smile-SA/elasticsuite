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
 * Delete action for Thesaurus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Delete extends ThesaurusController
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

        $identifier = $this->getRequest()->getParam('thesaurus_id', false);
        $model = $this->thesaurusFactory->create();
        if ($identifier) {
            $model->load($identifier);
            if (!$model->getThesaurusId()) {
                $this->messageManager->addError(__('This thesaurus no longer exists.'));

                return $resultRedirect->setPath('*/*/index');
            }
        }

        try {
            $this->thesaurusRepository->delete($model);
            $this->messageManager->addSuccess(__('You deleted the thesaurus %1.', $model->getName()));

            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', ['thesaurus_id' => $this->getRequest()->getParam('thesaurus_id')]);
        }
    }
}
