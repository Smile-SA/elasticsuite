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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteThesaurus\Api\ThesaurusRepositoryInterface;
use Smile\ElasticsuiteThesaurus\Model\ThesaurusFactory;

/**
 * Abstract Thesaurus controller
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
abstract class AbstractThesaurus extends Action
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
     * @param Context                      $context             Application context
     * @param PageFactory                  $resultPageFactory   Result Page factory
     * @param Registry                     $coreRegistry        Application registry
     * @param ThesaurusRepositoryInterface $thesaurusRepository Thesaurus Repository
     * @param ThesaurusFactory             $thesaurusFactory    Thesaurus Factory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
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
     * Check if allowed to manage thesaurus
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smile_ElasticsuiteThesaurus::manage');
    }
}
