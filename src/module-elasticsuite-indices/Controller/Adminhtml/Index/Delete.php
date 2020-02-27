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
use Magento\Backend\Model\View\Result\Redirect;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Smile\ElasticsuiteIndices\Helper\Index as IndexHelper;

/**
 * Indices Adminhtml Delete controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Delete extends AbstractAction
{
    /**
     * @var Client
     */
    private $esClient;

    /**
     * @var IndexHelper
     */
    protected $indexHelper;

    /**
     * @inheritDoc
     *
     * @param Context     $context     The current context.
     * @param Client      $esClient    ElasticSearch client.
     * @param IndexHelper $indexHelper Index helper.
     */
    public function __construct(Context $context, Client $esClient, IndexHelper $indexHelper)
    {
        $this->esClient = $esClient;
        $this->indexHelper = $indexHelper;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $indexName = $this->getRequest()->getParam('name', false);
        if ($indexName) {
            try {
                $index = $this->indexHelper->getElasticSuiteIndices(['index' => $indexName]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/index');
            }

            if (!$this->indexCanRemoved($indexName, current($index))) {
                $this->messageManager->addErrorMessage(__('Index can\'t be removed.'));

                return $resultRedirect->setPath('*/*/index');
            }
        }

        try {
            $this->esClient->deleteIndex($indexName);
            $this->messageManager->addSuccessMessage(__('You deleted the index %1.', $indexName));

            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/index');
        }
    }

    /**
     * Returns if index can removed.
     *
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     * @return bool
     */
    private function indexCanRemoved($indexName, $alias): bool
    {
        return $this->indexHelper->getIndexStatus($indexName, $alias) === IndexStatus::GHOST_STATUS;
    }
}
