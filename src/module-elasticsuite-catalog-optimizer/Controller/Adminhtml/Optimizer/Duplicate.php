<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\AbstractOptimizer as OptimizerController;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Optimizer Adminhtml duplicate controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Duplicate extends OptimizerController
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $optimizerId = (int) $this->getRequest()->getParam('id');
        $optimizer = null;
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $optimizer = $this->optimizerRepository->getById($optimizerId);
            $this->coreRegistry->register('current_optimizer', $optimizer);
            $newOptimizer = $this->optimizerCopier->copy($optimizer);
            $this->optimizerRepository->save($newOptimizer);
            $this->messageManager->addSuccessMessage(__('You duplicated the optimizer.'));
            $resultRedirect->setPath('*/*/edit', ['id' => $newOptimizer->getId()]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while duplicating the optimizer.'));
            $resultRedirect->setPath('*/*/index');
        }

        return $resultRedirect;
    }
}
