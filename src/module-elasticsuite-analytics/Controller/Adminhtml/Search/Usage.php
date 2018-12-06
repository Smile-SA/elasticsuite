<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Controller\Adminhtml\Search;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Search usage analytics dashboard controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Usage extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Constructor.
     *
     * @param Context     $context           Context
     * @param PageFactory $resultPageFactory Result page factory.
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Create result page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Smile_ElasticsuiteAnalytics::search_usage');
        $resultPage->addBreadcrumb(__('Search Engine'), __('Analytics'));
        $resultPage->getConfig()->getTitle()->prepend(__('Search Usage Analytics'));

        return $resultPage;
    }
}
