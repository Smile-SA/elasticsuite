<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Smile\ElasticsuiteIndices\Model\IndexSettingsProvider;

/**
 * Render the settings of an index
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Settings extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @var IndexSettingsProvider
     */
    protected $indexSettingsProvider;

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
     * @param Context               $context               The current context.
     * @param IndexSettingsProvider $indexSettingsProvider Index mapping provider.
     * @param PageFactory           $resultPageFactory     Page factory.
     * @param ForwardFactory        $resultForwardFactory  Forward factory.
     */
    public function __construct(
        Context $context,
        IndexSettingsProvider $indexSettingsProvider,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory
    ) {
        $this->indexSettingsProvider = $indexSettingsProvider;
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
            $this->indexSettingsProvider->getSettings($indexName);
        } catch (\Exception $e) {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('noroute');

            return $resultForward;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('smile_elasticsuite_indices_index_settings');
        $resultPage->getConfig()->getTitle()->prepend(__('Settings for index:') . ' ' . $indexName);

        return $resultPage;
    }
}
