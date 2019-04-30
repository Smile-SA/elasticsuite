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
use Smile\ElasticsuiteThesaurus\Model\ThesaurusFactory;

/**
 * Save action for Thesaurus
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Save extends ThesaurusController
{
    /**
     * Save a Thesaurus
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data         = $this->getRequest()->getPostValue();
        $redirectBack = $this->getRequest()->getParam('back', false);

        if ($data) {
            $identifier = $this->getRequest()->getParam('thesaurus_id');
            $model      = $this->thesaurusFactory->create();

            if ($identifier) {
                $model->load($identifier);
                if (!$model->getThesaurusId()) {
                    $this->messageManager->addError(__('This thesaurus no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            }

            $model->setData($data);
            $storeIds = $this->getRequest()->getParam('stores', null);
            if ($storeIds) {
                $model->setStoreIds($storeIds);
            }

            try {
                $this->thesaurusRepository->save($model);
                $this->messageManager->addSuccess(__('You saved the thesaurus %1.', $model->getName()));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                if ($redirectBack) {
                    return $resultRedirect->setPath('*/*/edit', ['thesaurus_id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                $returnParams = [
                    'thesaurus_id' => $this->getRequest()->getParam('thesaurus_id'),
                    'type'         => $this->getRequest()->getParam('type'),
                ];

                return $resultRedirect->setPath('*/*/edit', $returnParams);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
