<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Healthcheck;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\AbstractCheck;
use Smile\ElasticsuiteTracker\Api\EventQueueInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerConfig;

/**
 * Elasticsuite invalid tracker events healthcheck.
 *
 * Checks if there are invalid tracker events in the tracker event queue.
 * Invalid events usually indicate a misconfiguration or JavaScript issue
 * in Elasticsuite tracker tags on the frontend.
 *
 * Severity is dynamically determined:
 *  - NOTICE  : less than 10 000 invalid events
 *  - WARNING : 10 000 invalid events or more
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class TrackerInvalidEvents extends AbstractCheck
{
    /**
     * Threshold above which the severity becomes WARNING.
     */
    private const WARNING_THRESHOLD = 10000;

    /**
     * Tracker events queue.
     *
     * @var EventQueueInterface
     */
    private EventQueueInterface $eventQueue;

    /**
     * Tracker configuration helper.
     *
     * @var TrackerConfig
     */
    private TrackerConfig $config;

    /**
     * Authorization service.
     *
     * @var AuthorizationInterface
     */
    private AuthorizationInterface $authorization;

    /**
     * Cached invalid events count.
     *
     * @var integer|null
     */
    private ?int $invalidEventsCount = null;

    /**
     * Constructor.
     *
     * @param EventQueueInterface    $eventQueue    Event queue.
     * @param TrackerConfig          $config        Tracker config helper.
     * @param AuthorizationInterface $authorization User authorization.
     * @param UrlInterface           $urlBuilder    URL builder.
     * @param int                    $sortOrder     Sort order (default: 60).
     * @param int                    $severity      Severity level.
     */
    public function __construct(
        EventQueueInterface $eventQueue,
        TrackerConfig $config,
        AuthorizationInterface $authorization,
        UrlInterface $urlBuilder,
        int $sortOrder = 60,
        int $severity = MessageInterface::SEVERITY_NOTICE
    ) {
        parent::__construct($urlBuilder, $sortOrder, $severity);
        $this->eventQueue    = $eventQueue;
        $this->config        = $config;
        $this->authorization = $authorization;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'tracker_invalid_events';
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return ($this->getNumberOfInvalidTrackerEvents() > 0) ? CheckInterface::STATUS_FAILED : CheckInterface::STATUS_PASSED;
    }

    /**
     * {@inheritDoc}
     *
     * Severity is dynamically adjusted based on the number of invalid events.
     */
    public function getSeverity(): int
    {
        if ($this->getNumberOfInvalidTrackerEvents() >= self::WARNING_THRESHOLD) {
            return MessageInterface::SEVERITY_MINOR;
        }

        return MessageInterface::SEVERITY_NOTICE;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $description = __('No invalid Elasticsuite tracker events were detected.');
        $invalidEventsCount = $this->getNumberOfInvalidTrackerEvents();

        if ($invalidEventsCount > 0) {
            $messages = [];

            $messages[] = __(
                'There are <strong>%1 invalid Elasticsuite tracker events</strong> in the tracker events queue table. ' .
                'This could indicate that something is wrong with the Elasticsuite tracker tags in the frontend.',
                number_format($invalidEventsCount, 0, '.', '')
            );

            $messages[] = __(
                'Those invalid tracker events will not be indexed into your behavioral data indices ' .
                'and will automatically be deleted after <strong>%1 days</strong>.',
                $this->config->getEventsQueueCleanupDelay()
            );

            if ($this->authorization->isAllowed('Magento_Backend::smile_elasticsuite_tracker')) {
                $messages[] = __(
                    'You can decide to remove all of them immediately from the ' .
                    '<a href="%1"><strong>Elasticsuite tracker settings</strong></a> in the ' .
                    '"<strong>Queue cleanup configuration</strong>" section.',
                    $this->getTrackerConfigUrl()
                );
            }

            $description = implode('<br />', $messages);
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
     * Return the number of invalid tracker events.
     *
     * The result is cached to avoid multiple database calls.
     *
     * @return int
     */
    private function getNumberOfInvalidTrackerEvents(): int
    {
        if (is_null($this->invalidEventsCount)) {
            try {
                $this->invalidEventsCount = $this->eventQueue->getInvalidEventsCount();
            } catch (LocalizedException $e) {
                $this->invalidEventsCount = 0;
            }
        }

        return $this->invalidEventsCount;
    }

    /**
     * Get URL to the Elasticsuite tracker configuration page.
     *
     * @return string
     */
    private function getTrackerConfigUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            [
                'section'   => 'smile_elasticsuite_tracker',
                '_fragment' => 'smile_elasticsuite_tracker_queue_cleanup-link',
            ]
        );
    }
}
