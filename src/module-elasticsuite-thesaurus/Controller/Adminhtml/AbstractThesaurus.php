<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticSuiteThesaurus\Api\ThesaurusRepositoryInterface;
use Smile\ElasticSuiteThesaurus\Model\ThesaurusFactory;

/**
 * Abstract Thesaurus controller
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
abstract class AbstractThesaurus extends Action
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
     * @var ThesaurusRepositoryInterface
     */
    protected $thesaurusRepository;

    /**
     * Thesaurus Factory
     *
     * @var ThesaurusFactory
     */
    protected $thesaurusFactory;


    /**
     * Abstract constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context             Application context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory   Tesult Page factory
     * @param \Magento\Framework\Registry                $coreRegistry        Application registry
     * @param ThesaurusRepositoryInterface               $thesaurusRepository Thesaurus Repository
     * @param ThesaurusFactory                           $thesaurusFactory    Thesaurus Factory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        ThesaurusRepositoryInterface $thesaurusRepository,
        ThesaurusFactory $thesaurusFactory
    ) {
        $this->resultPageFactory   = $resultPageFactory;
        $this->coreRegistry        = $coreRegistry;
        $this->thesaurusRepository = $thesaurusRepository;
        $this->thesaurusFactory    = $thesaurusFactory;

        parent::__construct($context);
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
        $resultPage->setActiveMenu('Smile_ElasticsuiteThesaurus::manage')
            ->addBreadcrumb(__('Thesaurus'), __('Thesaurus'));

        return $resultPage;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * Check if allowed to manage thesaurus
     *
     * @return bool
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _isAllowed()
    {
        //@codingStandardsIgnoreEnd
        return $this->_authorization->isAllowed('Smile_ElasticsuiteThesaurus::manage');
    }
}
