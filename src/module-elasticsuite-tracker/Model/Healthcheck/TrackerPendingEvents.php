<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Healthcheck;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteAnalytics\Helper\Data as AnalyticsHelper;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\AbstractCheck;
use Smile\ElasticsuiteTracker\Api\EventQueueInterface;

/**
 * Elasticsuite tracker pending events check.
 * Checks if the Elasticsuite tracker has pending events in the event queue,
 * which may indicate that the cron is not running.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class TrackerPendingEvents extends AbstractCheck
{
    /**
     * Events queue.
     * @var EventQueueInterface
     */
    private EventQueueInterface $eventQueue;

    /**
     * Analytics config helper.
     * @var AnalyticsHelper
     */
    private AnalyticsHelper $config;

    /**
     * Constructor.
     *
     * @param EventQueueInterface $eventQueue Events queue.
     * @param AnalyticsHelper     $config     Analytics config helper.
     * @param UrlInterface        $urlBuilder URL builder.
     * @param int                 $sortOrder  Sort order (default: 50).
     * @param int                 $severity   Severity level.
     */
    public function __construct(
        EventQueueInterface $eventQueue,
        AnalyticsHelper $config,
        UrlInterface $urlBuilder,
        int $sortOrder = 50,
        int $severity = MessageInterface::SEVERITY_MINOR
    ) {
        parent::__construct($urlBuilder, $sortOrder, $severity);
        $this->eventQueue = $eventQueue;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'tracker_pending_events';
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return ($this->getPendingEventsCount() ? CheckInterface::STATUS_FAILED : CheckInterface::STATUS_PASSED);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $description = __('The tracker events seem to be regularly indexed as expected.');
        $pendingEventsCount = $this->getPendingEventsCount();

        if ($pendingEventsCount) {
            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    __(
                        'There are currently %1 tracker events created more than %2 hours ago in the events queue table.',
                        $pendingEventsCount,
                        $this->config->getMaxHoursBeforeEventsWarning()
                    ),
                    __(
                        'If you think the Elasticsuite <a href="%1"><strong>Search Usage screen</strong></a> ' .
                        'is lacking some behavioral data, make sure the "elasticsuite_index_log_event" ' .
                        'cronjob is running regularly enough.',
                        $this->getSearchUsageScreenUrl()
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        return $description;
    }

    /**
     * {@inheritDoc}
     */
    public function isDisplayed(): bool
    {
        return true;
    }

    /**
     * Return the number of pending events in the database.
     *
     * @return int
     */
    private function getPendingEventsCount(): int
    {
        $eventsWarningAfter = $this->config->getMaxHoursBeforeEventsWarning();
        try {
            $pendingEventsCount = $this->eventQueue->getPendingEventsCount($eventsWarningAfter);
        } catch (LocalizedException $e) {
            $pendingEventsCount = 0;
        }

        return $pendingEventsCount;
    }


    /**
     * Get URL to the Search Usage Screen.
     *
     * @return string
     */
    private function getSearchUsageScreenUrl(): string
    {
        return $this->urlBuilder->getUrl('smile_elasticsuite_analytics/search/usage');
    }
}
