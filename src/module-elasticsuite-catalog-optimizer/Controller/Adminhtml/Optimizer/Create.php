<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
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
class Create extends OptimizerController
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->createPage();

        $resultPage->setActiveMenu('Smile_ElasticsuiteCatalogOptimizer::optimizers');
        $resultPage->getConfig()->getTitle()->prepend(__('New Optimizer'));

        return $resultPage;
    }
}
