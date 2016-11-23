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
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Optimizer Adminhtml Index controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Edit extends OptimizerController
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $optimizerId = (int) $this->getRequest()->getParam('id');
        $optimizer = null;

        try {
            $optimizer = $this->optimizerRepository->getById($optimizerId);
            $this->coreRegistry->register('current_optimizer', $optimizer);
            $resultPage->getConfig()->getTitle()->prepend(__('Edit %1', $optimizer->getName()));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while editing the optimizer.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*/index');

            return $resultRedirect;
        }

        $resultPage->addBreadcrumb(__('Optimizer'), __('Optimizer'));

        return $resultPage;
    }
}
