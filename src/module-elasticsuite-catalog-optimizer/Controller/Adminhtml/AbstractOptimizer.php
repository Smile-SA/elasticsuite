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

/**
 * Abstract Optimizer controller
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
     * @var \Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface
     */
    protected $optimizerRepository;

    /**
     * Optimizer Factory
     *
     * @var \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory
     */
    protected $optimizerFactory;

    /**
     * Abstract constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                    $context             Application context.
     * @param \Magento\Framework\View\Result\PageFactory                             $resultPageFactory   Result Page factory.
     * @param \Magento\Framework\Registry                                            $coreRegistry        Application registry.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface   $optimizerRepository Optimizer Repository.
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory $optimizerFactory    Optimizer Factory.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface $optimizerRepository,
        \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory $optimizerFactory
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
     * Check if allowed to manage optimizer.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smile_ElasticsuiteCatalogOptimizer::manage');
    }
}
