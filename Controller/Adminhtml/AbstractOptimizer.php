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
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\OptimizerFactory;

/**
 * Abstract Retailer controller
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
abstract class AbstractOptimizer extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory|null
     */
    protected $resultPageFactory = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var OptimizerRepositoryInterface
     */
    protected $optimizerRepository;

    /**
     * Optimizer Factory
     *
     * @var OptimizerFactory
     */
    protected $optimizerFactory;
    
    /**
     * Abstract constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context              Application context
     * @param PageFactory                         $resultPageFactory    Result Page factory
     * @param \Magento\Framework\Registry         $coreRegistry         Application registry
     * @param OptimizerRepositoryInterface        $optimizerRepository  Optimizer Repository
     * @param OptimizerFactory                    $optimizerFactory     Optimizer Factory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        OptimizerRepositoryInterface $optimizerRepository,
        OptimizerFactory $optimizerFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory    = $resultPageFactory;
        $this->coreRegistry         = $coreRegistry;
        $this->optimizerRepository  = $optimizerRepository;
        $this->optimizerFactory     = $optimizerFactory;
    }

    /**
     * Create result page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function createPage()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Smile_ElasticsuiteCatalogOptimizer::optimizer')
            ->addBreadcrumb(__('Optimizer'), __('Optimizer'));

        return $resultPage;
    }

    /**
     * Check if allowed to manage retailer
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smile_ElasticsuiteCatalogOptimizer::manage');
    }
}
