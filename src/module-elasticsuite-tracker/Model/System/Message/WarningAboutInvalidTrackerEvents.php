<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Model\System\Message;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Smile\ElasticsuiteTracker\Api\EventQueueInterface;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerConfig;
use Magento\Framework\UrlInterface;

/**
 * ElasticSuite warning about invalid tracker events
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class WarningAboutInvalidTrackerEvents implements MessageInterface
{
    /** @var EventQueueInterface */
    private $eventQueue;

    /** @var TrackerConfig */
    private $config;

    /** @var AuthorizationInterface */
    private $authorization;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var integer */
    private $invalidEventsCount;

    /**
     * Constructor.
     *
     * @param EventQueueInterface    $eventQueue    Event queue.
     * @param TrackerConfig          $config        Tracker config helper.
     * @param AuthorizationInterface $authorization User authorization.
     * @param UrlInterface           $urlBuilder    Url builder.
     */
    public function __construct(
        EventQueueInterface $eventQueue,
        TrackerConfig $config,
        AuthorizationInterface $authorization,
        UrlInterface $urlBuilder
    ) {
        $this->eventQueue = $eventQueue;
        $this->config = $config;
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentity(): string
    {
        return hash('sha256', 'ELASTICSUITE_INVALID_TRACKER_EVENTS_WARNING');
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_MINOR;
    }

    /**
     * {@inheritdoc}
     */
    public function getText(): string
    {
        $messageDetails = '';

        // @codingStandardsIgnoreStart
        $messageDetails .= __(
            'There are <strong>%1 invalid Elasticsuite tracker events</strong> in the tracker events queue table. This could indicate that something is wrong with the Elasticsuite tracker tags in the frontend.',
            $this->getNumberOfInvalidTrackerEvents()
        ) . '<br/>';

        $messageDetails .= __(
            'Those invalid tracker events will not be indexed into your behavioral data indices and will automatically be deleted after %1 days.',
            $this->config->getEventsQueueCleanupDelay()
        );

        if ($this->authorization->isAllowed('Magento_Backend::smile_elasticsuite_tracker')) {
            $routeParams = [
                'section'     => 'smile_elasticsuite_tracker',
                '_fragment'  => 'smile_elasticsuite_tracker_queue_cleanup-link',
            ];
            $messageDetails .= '<br />' . __(
                'You can decide to remove all of them immediately from the <a href="%1"><strong>Elasticsuite tracker settings</strong></a> in the "<strong>Queue cleanup configuration</strong>" section.',
                $this->urlBuilder->getUrl(
                    'admin/system_config/edit',
                    $routeParams
                )
            );
        }
        // @codingStandardsIgnoreEnd

        return $messageDetails;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed(): bool
    {
        return ($this->getNumberOfInvalidTrackerEvents() > 0);
    }

    /**
     * Return the number of invalid tracker events.
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
}
