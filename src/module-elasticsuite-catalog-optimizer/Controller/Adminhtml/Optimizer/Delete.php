<?php
/**
 * DISCLAIMER
 *
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
class Delete extends OptimizerController
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $identifier = $this->getRequest()->getParam('id', false);
        $model = $this->optimizerFactory->create();
        if ($identifier) {
            $model = $this->optimizerRepository->getById($identifier);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This optimizer no longer exists.'));

                return $resultRedirect->setPath('*/*/index');
            }
        }

        try {
            $this->optimizerRepository->delete($model);
            $this->messageManager->addSuccessMessage(__('You deleted the optimizer %1.', $model->getName()));

            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
    }
}
