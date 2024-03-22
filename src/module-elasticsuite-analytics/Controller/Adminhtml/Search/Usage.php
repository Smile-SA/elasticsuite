<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Controller\Adminhtml\Search;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteAnalytics\Helper\Data as AnalyticsHelper;
use Smile\ElasticsuiteAnalytics\Model\Report\Context as ReportContext;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Api\EventQueueInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;

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
     * @var IndexOperationInterface
     */
    private $indexOperation;

    /**
     * @var EventQueueInterface
     */
    private $eventQueue;

    /**
     * @var AnalyticsHelper
     */
    private $config;

    /**
     * @var ReportContext
     */
    private $reportContext;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Constructor.
     *
     * @param Context                 $context           Context.
     * @param IndexOperationInterface $indexOperation    Index operation.
     * @param EventQueueInterface     $eventQueue        Events queue.
     * @param AnalyticsHelper         $config            Analytics config helper.
     * @param ReportContext           $reportContext     Report context.
     * @param PageFactory             $resultPageFactory Result page factory.
     */
    public function __construct(
        Context $context,
        IndexOperationInterface $indexOperation,
        EventQueueInterface $eventQueue,
        AnalyticsHelper $config,
        ReportContext $reportContext,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->indexOperation           = $indexOperation;
        $this->eventQueue               = $eventQueue;
        $this->config                   = $config;
        $this->reportContext            = $reportContext;
        $this->resultPageFactory        = $resultPageFactory;
    }

    /**
     * Create result page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $indexIdentifiers = [SessionIndexInterface::INDEX_IDENTIFIER, EventIndexInterface::INDEX_IDENTIFIER];
        foreach ($indexIdentifiers as $indexIdentifier) {
            if (!$this->checkIndexPresence($indexIdentifier)) {
                $this->messageManager->addWarningMessage(
                    "{$indexIdentifier} index does not exist yet. Make sure everything is reindexed."
                );
            }
        }

        $this->addEventsWarningIfRequired();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Smile_ElasticsuiteAnalytics::search_usage');
        $resultPage->addBreadcrumb(__('Search Engine'), __('Analytics'));
        $resultPage->getConfig()->getTitle()->prepend(__('Search Usage Analytics'));

        return $resultPage;
    }

    /**
     * Check an index is available
     *
     * @param string $indexIdentifier Index identifier.
     *
     * @return boolean
     */
    private function checkIndexPresence($indexIdentifier)
    {
        return $this->indexOperation->indexExists($indexIdentifier, $this->reportContext->getStoreId());
    }

    /**
     * Check if some events are waiting being indexed for too long and if so, display a notice message.
     *
     * @return void
     */
    private function addEventsWarningIfRequired()
    {
        $eventsWarningAfter = $this->config->getMaxHoursBeforeEventsWarning();
        try {
            $pendingEventsCount = $this->eventQueue->getPendingEventsCount($eventsWarningAfter);
        } catch (LocalizedException $e) {
            $pendingEventsCount = 0;
        }
        if ($pendingEventsCount > 0) {
            $this->messageManager->addNoticeMessage(
                __(
                    'There are currently %1 events created more than %2 hours ago in the events queue table.'
                    // phpcs:ignore Generic.Files.LineLength
                    . ' If you think this screen is lacking some behavioral data, make sure the "elasticsuite_index_log_event" cronjob is running frequently enough.',
                    $pendingEventsCount,
                    $eventsWarningAfter
                )
            );
        }
    }
}
