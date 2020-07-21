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
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Copier;

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
     * @var PageFactory|null
     */
    protected $resultPageFactory = null;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var OptimizerRepositoryInterface
     */
    protected $optimizerRepository;

    /**
     * Optimizer Factory
     *
     * @var OptimizerInterfaceFactory
     */
    protected $optimizerFactory;

    /**
     * @var Copier
     */
    protected $optimizerCopier;

    /**
     * Abstract constructor.
     *
     * @param Context                      $context             Application context.
     * @param PageFactory                  $resultPageFactory   Result Page factory.
     * @param Registry                     $coreRegistry        Application registry.
     * @param OptimizerRepositoryInterface $optimizerRepository Optimizer Repository.
     * @param OptimizerInterfaceFactory    $optimizerFactory    Optimizer Factory.
     * @param Copier                       $optimizerCopier     Optimizer Copier.
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        OptimizerRepositoryInterface $optimizerRepository,
        OptimizerInterfaceFactory $optimizerFactory,
        Copier $optimizerCopier
    ) {
        parent::__construct($context);

        $this->resultPageFactory    = $resultPageFactory;
        $this->coreRegistry         = $coreRegistry;
        $this->optimizerRepository  = $optimizerRepository;
        $this->optimizerFactory     = $optimizerFactory;
        $this->optimizerCopier      = $optimizerCopier;
    }

    /**
     * Create result page
     *
     * @return Page
     */
    protected function createPage()
    {
        /** @var Page $resultPage */
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
