<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Smile\ElasticsuiteIndices\Model\IndexMappingProvider;

/**
 * Indices Adminhtml View controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Mapping extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @var IndexMappingProvider
     */
    protected $indexMappingProvider;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @inheritDoc
     *
     * @param Context              $context              The current context.
     * @param IndexMappingProvider $indexMappingProvider Index mapping provider.
     * @param PageFactory          $resultPageFactory    Page factory.
     * @param ForwardFactory       $resultForwardFactory Forward factory.
     */
    public function __construct(
        Context $context,
        IndexMappingProvider $indexMappingProvider,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory
    ) {
        $this->indexMappingProvider = $indexMappingProvider;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $indexName = $this->getRequest()->getParam('name');
        try {
            $this->indexMappingProvider->getMapping($indexName);
        } catch (\Exception $e) {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('noroute');

            return $resultForward;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('smile_elasticsuite_indices_index_mapping');
        $resultPage->getConfig()->getTitle()->prepend(__('Mapping for index:') . ' ' . $indexName);

        return $resultPage;
    }
}
