<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\AbstractOptimizer as OptimizerController;

/**
 * Optimizer Adminhtml Index controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Save extends OptimizerController
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        $redirectBack = $this->getRequest()->getParam('back', false);

        if ($data) {
            $identifier = $this->getRequest()->getParam('id');
            $model = $this->optimizerFactory->create();

            if ($identifier) {
                $model = $this->optimizerRepository->getById($identifier);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This optimizer no longer exists.'));

                    return $resultRedirect->setPath('*/*/');
                }
            }

            if (empty($data['optimizer_id'])) {
                $data['optimizer_id'] = null;
            }

            $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                return $resultRedirect->setPath('*/*/edit', ['id' => $identifier]);
            }

            $model->setData($data);
            $ruleConditionPost = $this->getRequest()->getParam('rule_condition', []);
            $model->getRuleCondition()->loadPost($ruleConditionPost);

            try {
                $this->optimizerRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the optimizer %1.', $model->getName()));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                if ($redirectBack) {
                    $redirectParams = ['id' => $model->getId()];

                    return $resultRedirect->setPath('*/*/edit', $redirectParams);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData($data);

                $returnParams = ['id' => $model->getId()];

                return $resultRedirect->setPath('*/*/edit', $returnParams);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}
